<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\Database\Entity\Channel as DbChannel;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\UsedActionToken as DbToken;
use App\Data\ID;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\Exception\IllegalMoveException;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Service\ShipsService;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipMovementService extends ShipsService
{
    private const TOKEN_EXPIRY = 'PT1H';

    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        bool $reverseDirection,
        int $journeyTime,
        string $tokenKey
    ): MoveShipToken {
        $id = ID::makeIDFromKey(DbToken::class, $tokenKey);
        $token = $this->tokenHandler->makeToken(
            MoveShipToken::makeClaims(
                $ship->getId(),
                $channel->getId(),
                $reverseDirection,
                $journeyTime
            ),
            self::TOKEN_EXPIRY,
            $id
        );
        return new MoveShipToken($token);
    }

    // todo - move to shipsService
    public function useMoveShipToken(
        string $token
    ): void {
        $token = $this->tokenHandler->parseTokenFromString($token);
        $tokenDetail = new MoveShipToken($token);

        $shipId = $tokenDetail->getShipId();
        $channelId = $tokenDetail->getChannelId();
        $reversed = $tokenDetail->isReversed();

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
            new \DateInterval('PT' . $tokenDetail->getJourneyTime() . 'M')
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

            // update the users score - todo - calculate how much the rate delta should be
            $this->entityManager->getUserRepo()->updateScore($ship->owner, 1);

            // todo - mark any abilities as used

            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($token);
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
        // todo - this return should contain the arrival time
    }



    // todo - check the logic below here


    public function moveShipToLocation(
        UuidInterface $shipId,
        UuidInterface $locationId
    ): void {
        $shipRepo = $this->entityManager->getShipRepo();

        // fetch the ship
        $ship = $shipRepo->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $locationType = ID::getIDType($locationId);

        // fetch the ships current location
        $currentShipLocation = $this->entityManager->getShipLocationRepo()
            ->getCurrentForShipId($ship->id, Query::HYDRATE_OBJECT);

        if ($locationType === DbPort::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->port) {
                throw new IllegalMoveException('You can only move into a port if you came from a channel');
            }
            $this->moveShipToPortId($ship, $currentShipLocation, $locationId);
            return;
        }

        if ($locationType === DbChannel::class) {
            // if the current location is a port this is an Illegal move
            if ($currentShipLocation->channel) {
                throw new IllegalMoveException('You can only move into a channel if you came from a port');
            }
            die('what!'); // todo
//            $this->moveShipToChannelId($ship, $currentShipLocation, $locationId);
            return;
        }

        throw new \InvalidArgumentException('Invalid destination ID');
    }


    private function moveShipToPortId(
        DbShip $ship,
        DbShipLocation $currentShipLocation,
        UuidInterface $portId
    ) {
        // todo - remove this method?!
        throw new \InvalidArgumentException('Check this');

        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        if (!$port) {
            throw new \InvalidArgumentException('No such port');
        }

        $userRepo =  $this->entityManager->getUserRepo();
        $user = $userRepo->getByID($ship->owner);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // remove the old ship location
            $currentShipLocation->isCurrent = false;
            $currentShipLocation->exitTime = $this->currentTime;
            $this->entityManager->persist($currentShipLocation);

            // make a new ship location
            $newLocation = new DbShipLocation(
                ID::makeNewID(DbShipLocation::class),
                $ship,
                $port,
                null,
                $this->currentTime
            );
            $this->entityManager->persist($newLocation);

            // update the users score


            // todo - add this port to the list of visited ports for this user
            // calculate the user's new rank and cache it

            // move all crates on the ship into the port
            // get the crates
            $crateLocations = $this->entityManager->getCrateLocationRepo()
                ->findCurrentForShipID($ship->id, Query::HYDRATE_OBJECT);
            if (!empty($crateLocations)) {
                foreach ($crateLocations as $crateLocation) {
                    $crateLocation->isCurrent = false;
                    $this->entityManager->persist($crateLocation);

                    $newLocation = new DbCrateLocation(
                        ID::makeNewID(DbCrateLocation::class),
                        $crateLocation->crate,
                        $port,
                        null
                    );
                    $this->entityManager->persist($newLocation);
                }
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Failed to move ship into channel. Rollback transaction');
            throw $e;
        }
    }
}
