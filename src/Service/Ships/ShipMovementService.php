<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\Database\Entity\Channel as DbChannel;
use App\Data\Database\Entity\PortVisit as DbPortVisit;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Service\ShipsService;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;

class ShipMovementService extends ShipsService
{
    use DeltaTrait;

    private const AUTO_MOVE_TIME = 'PT1H';

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
            $journeyTime
        ));
        return new MoveShipToken($token->getJsonToken(), (string)$token);
    }

    public function useMoveShipToken(
        MoveShipToken $token
    ): ShipLocation {
        $shipId = $token->getShipId();
        $channelId = $token->getChannelId();
        $reversed = $token->isReversed();
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

        $exitTime = $now->add(
            new \DateInterval('PT' . $token->getJourneyTime() . 'S')
        );

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

            $this->logger->info('Marking token as used');

            $this->tokenHandler->markAsUsed($token->getOriginalToken());
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

    public function parseMoveShipToken(
        string $tokenString
    ): MoveShipToken {
        return new MoveShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function autoMoveShips(
        DateTimeImmutable $now,
        int $limit
    ): int {
        $before = $now->sub(new DateInterval(self::AUTO_MOVE_TIME));

        // find ships with auto-move that have been sitting in a port for a while
        $ships = $this->entityManager->getShipLocationRepo()->getAutoMoveShipsInPortBefore($before);

        foreach ($ships as $ship) {
            $this->autoMove($ship, $now);
        }

        return \count($ships);
    }

    public function autoMove(DbShipLocation $shipLocation, DateTimeImmutable $now): void
    {
        $ship = $shipLocation->ship;
        $player = $ship->owner;
        $port = $shipLocation->port;

        // find all the possible directions they can use
        $channels = $this->entityManager->getChannelRepo()
            ->getAllLinkedToPortId($port->id);

        // todo - let's handle all this in an abstract object
        // todo - filter out channels that can't be travelled because the ship is not strong enough
        // or the owner doesn't meet the minimum rank
        // $playerRank = $this->entityManager->getPlayerRankRepo()->getCurrentForPlayerId($ship->owner->id);
        //($ship->strength, $shipLocation->port->id);

        $earliestVisited = null;
        $visitedChannel = null;
        $unvisitedChannel = null;
        $reversed = false;

        // find one which the player has NOT been to before
        // if not found, choose the one the player hasn't been to most recently
        \shuffle($channels);
        foreach ($channels as $channel) {
            /** @var DbChannel $channel */
            $reversed = $port->id->equals($channel->toPort->id);
            $destinationPort = $reversed ? $channel->fromPort : $channel->toPort;
            /** @var DbPortVisit $visited */
            $visited = $this->entityManager->getPortVisitRepo()
                ->getForPortAndUser($destinationPort->id, $player->id);
            if ($visited) {
                if (!$earliestVisited || $visited->firstVisited < $earliestVisited) {
                    $visitedChannel =$channel;
                    $earliestVisited = $visited->firstVisited;
                }
            } else {
                $unvisitedChannel = $channel;
            }
        }
        $chosenChannel = $unvisitedChannel ?? $visitedChannel;
        if (!$chosenChannel) {
            throw new \RuntimeException('Could not find a channel');
        }

        $journeyTime = $this->algorithm->getJourneyTime(
            $chosenChannel->distance,
            $ship
        );

        $exitTime = $now->add(
            new \DateInterval('PT' . $journeyTime . 'S')
        );

        $delta = $this->calculateDelta($ship->id, $chosenChannel->distance, $now, $exitTime);

        $this->entityManager->transactional(function () use (
            $ship,
            $chosenChannel,
            $now,
            $exitTime,
            $reversed,
            $delta
        ) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getShipLocationRepo()->exitLocation($ship);

            $this->logger->info('Creating new location');
            $this->entityManager->getShipLocationRepo()->makeInChannel(
                $ship,
                $chosenChannel,
                $now,
                $exitTime,
                $reversed
            );

            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($ship->owner, $delta);

            $this->logger->notice(sprintf(
                '[DEPARTURE] Ship: %s, Channel: %s, Reversed: %s',
                (string)$ship->id,
                (string)$chosenChannel->id,
                (string)$reversed
            ));
        });
    }
}
