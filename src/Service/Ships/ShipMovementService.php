<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\TokenProvider;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Service\ShipsService;
use DateInterval;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipMovementService extends ShipsService
{
    use DeltaTrait;

    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        User $owner,
        bool $reverseDirection,
        int $journeyTime,
        string $tokenKey
    ): MoveShipToken {
        $token = $this->tokenHandler->makeToken(...MoveShipToken::make(
            $this->uuidFactory->uuid5(Uuid::NIL, \sha1($tokenKey)),
            $ship->getId(),
            $channel->getId(),
            $owner->getId(),
            $reverseDirection,
            $journeyTime,
            ));
        return new MoveShipToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(MoveShipToken::class, $this->dateTimeFactory->now()),
            );
    }

    public function moveShip(
        UuidInterface $shipId,
        UuidInterface $channelId,
        bool $reversed,
        DateInterval $journeyTime,
        MoveShipToken $token = null
    ) {
        $now = $this->dateTimeFactory->now();

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        /** @var \App\Data\Database\Entity\Channel $channel */
        $channel = $this->entityManager->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        $exitTime = $now->add($journeyTime);

        $delta = $this->calculateDelta($shipId, $channel->distance, $now, $exitTime);

        $this->entityManager->transactional(function () use (
            $ship,
            $channel,
            $now,
            $exitTime,
            $reversed,
            $delta,
            $token
        ) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getShipLocationRepo()->exitLocation($ship);

            $this->logger->info('Creating new location');
            $this->entityManager->getShipLocationRepo()->makeInChannel(
                $ship,
                $channel,
                $now,
                $exitTime,
                $reversed
            );

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($ship->owner, $delta);


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

        $newLocation = $this->entityManager->getShipLocationRepo()->getCurrentForShipId(
            $shipId
        );
        return $this->mapperFactory->createShipLocationMapper()->getShipLocation($newLocation);
    }

    public function useMoveShipToken(
        MoveShipToken $token
    ): ShipLocation {
        return $this->moveShip(
            $token->getShipId(),
            $token->getChannelId(),
            $token->isReversed(),
            $token->getJourneyTime(),
            $token
        );
    }

    public function parseMoveShipToken(
        string $tokenString
    ): MoveShipToken {
        return new MoveShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }
}
