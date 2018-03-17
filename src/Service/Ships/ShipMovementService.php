<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\Database\Entity\UsedActionToken as DbToken;
use App\Data\ID;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Costs;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Service\ShipsService;
use Doctrine\ORM\Query;

class ShipMovementService extends ShipsService
{
    private const TOKEN_EXPIRY = 'PT1H';

    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        User $owner,
        bool $reverseDirection,
        int $journeyTime,
        string $tokenKey,
        ?Port $departingPort
    ): MoveShipToken {
        $id = ID::makeIDFromKey(DbToken::class, $tokenKey);
        $token = $this->tokenHandler->makeToken(
            MoveShipToken::makeClaims(
                $ship->getId(),
                $channel->getId(),
                $owner->getId(),
                $reverseDirection,
                $journeyTime,
                $departingPort ? $departingPort->getId() : null
            ),
            self::TOKEN_EXPIRY,
            $id
        );
        return new MoveShipToken($token);
    }

    public function useMoveShipToken(
        MoveShipToken $token
    ): ShipLocation {
        $shipId = $token->getShipId();
        $channelId = $token->getChannelId();
        $reversed = $token->isReversed();

        $firstPort = null;
        $firstPortId = $token->getFirstPortId(); // was it the first journey?
        if ($firstPortId) {
            $firstPort = $this->entityManager->getPortRepo()->getByID($firstPortId, Query::HYDRATE_OBJECT);
        }

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $channel = $this->entityManager->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        // todo - adjust exit time if any abilities were applied
        $exitTime = $this->currentTime->add(
            new \DateInterval('PT' . $token->getJourneyTime() . 'M')
        );

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getShipLocationRepo()->exitLocation($ship);

            $this->logger->info('Creating new location');
            $this->entityManager->getShipLocationRepo()->makeInChannel(
                $ship,
                $channel,
                $exitTime,
                $reversed
            );

            if ($firstPort) {
                $this->logger->info('Recording users first visit to a port');
                $this->entityManager->getPortVisitRepo()->recordVisit(
                    $ship->owner,
                    $firstPort
                );
            }

            // update the users score - todo - calculate how much the rate delta should be
            $this->entityManager->getUserRepo()->updateScoreRate($ship->owner, Costs::DELTA_SHIP_DEPARTURE);

            // todo - mark any abilities as used

            $this->logger->info('Marking token as used');

            $this->tokenHandler->markAsUsed($token->getOriginalToken());

            $this->logger->info('Committing transaction');
            $this->entityManager->getConnection()->commit();
            $this->logger->notice(sprintf(
                '[DEPARTURE] Ship: %s, Channel: %s, Reversed: %s',
                (string)$shipId,
                (string)$channelId,
                (string)$reversed
            ));
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back "useMoveShipToken" transaction');
            throw $e;
        }

        $newLocation = $this->entityManager->getShipLocationRepo()->getCurrentForShipId(
            $shipId
        );
        return $this->mapperFactory->createShipLocationMapper()->getShipLocation($newLocation);
    }

    public function parseMoveShipToken(
        string $tokenString
    ): MoveShipToken {
        return new MoveShipToken($this->tokenHandler->parseTokenFromString($tokenString));
    }
}