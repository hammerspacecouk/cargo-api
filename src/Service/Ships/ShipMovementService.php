<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\User as DbUser;
use App\Data\TokenProvider;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\Entity\UserEffect;
use App\Domain\ValueObject\TacticalEffect;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Domain\ValueObject\TokenId;
use App\Service\ShipsService;
use DateInterval;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Dates\intervalToSeconds;

class ShipMovementService extends ShipsService
{
    /**
     * @param Ship $ship
     * @param Channel $channel
     * @param User $owner
     * @param bool $reverseDirection
     * @param int $journeyTime
     * @param int $earnings
     * @param UuidInterface $currentLocation
     * @param TacticalEffect[] $tacticalEffectsToExpire
     * @return MoveShipToken
     */
    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        User $owner,
        bool $reverseDirection,
        int $journeyTime,
        int $earnings,
        UuidInterface $currentLocation,
        array $tacticalEffectsToExpire
    ): MoveShipToken {
        $token = $this->tokenHandler->makeToken(...MoveShipToken::make(
            new TokenId($this->uuidFactory->uuid5(
                'a08d1220-de3a-44e6-9a21-727b12dda668',
                $currentLocation->toString(),
            )),
            $ship->getId(),
            $channel->getId(),
            $owner->getId(),
            $reverseDirection,
            $journeyTime,
            $earnings,
            $tacticalEffectsToExpire,
        ));
        return new MoveShipToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(MoveShipToken::class, $this->dateTimeFactory->now()),
        );
    }

    /**
     * @param UuidInterface $shipId
     * @param UuidInterface $channelId
     * @param bool $reversed
     * @param DateInterval $journeyTime
     * @param int $earnings
     * @param MoveShipToken|null $token
     * @param UuidInterface[] $effectIdsToExpire
     * @return ShipLocation
     */
    public function moveShip(
        UuidInterface $shipId,
        UuidInterface $channelId,
        bool $reversed,
        DateInterval $journeyTime,
        int $earnings,
        MoveShipToken $token = null,
        array $effectIdsToExpire = []
    ): ShipLocation {
        $now = $this->dateTimeFactory->now();

        /** @var DbShip $ship */
        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        /** @var \App\Data\Database\Entity\Channel|null $channel */
        $channel = $this->entityManager->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        $exitTime = $now->add($journeyTime);

        // delta is the earnings per second
        $delta = (int)\ceil($earnings / intervalToSeconds($journeyTime));

        $this->entityManager->transactional(function () use (
            $ship,
            $channel,
            $now,
            $exitTime,
            $reversed,
            $delta,
            $token,
            $effectIdsToExpire
        ) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getShipLocationRepo()->exitLocation($ship);

            $this->logger->info('Creating new location');
            $this->entityManager->getShipLocationRepo()->makeInChannel(
                $ship,
                $channel,
                $now,
                $exitTime,
                $reversed,
                $delta
            );

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($ship->owner, $delta);

            // expire effects
            $this->entityManager->getActiveEffectRepo()->expireByIds($effectIdsToExpire);

            if ($token) {
                $this->logger->info('Marking token as used');
                $this->tokenHandler->markAsUsed($token->getOriginalToken());
            }

            $this->logger->notice(sprintf(
                '[DEPARTURE] Ship: %s, Channel: %s, Reversed: %s',
                (string)$ship->id,
                (string)$channel->id,
                (string)$reversed
            ));
        });

        // as a safety check if some race condition happened, confirm the user delta
        $owner = $ship->owner; // lazy loaded
        $expectedDelta = $this->entityManager->getShipLocationRepo()->sumDeltaForUserId($owner->id);
        $owner->scoreRate = $expectedDelta;
        $this->entityManager->persist($owner);
        $this->entityManager->flush();

        return $this->mapperFactory->createShipLocationMapper()->getShipLocation(
            $this->entityManager->getShipLocationRepo()->getCurrentForShipId($shipId)
        );
    }

    public function useMoveShipToken(
        MoveShipToken $token
    ): ShipLocation {
        return $this->moveShip(
            $token->getShipId(),
            $token->getChannelId(),
            $token->isReversed(),
            $token->getJourneyTime(),
            $token->getEarnings(),
            $token,
            $token->getEffectIdsToExpire(),
        );
    }

    public function parseMoveShipToken(
        string $tokenString
    ): MoveShipToken {
        return new MoveShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }
}
