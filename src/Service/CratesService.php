<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\TokenProvider;
use App\Domain\Entity\Crate;
use App\Domain\Entity\CrateLocation;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\Exception\OutdatedMoveException;
use App\Domain\ValueObject\Token\Action\MoveCrate\DropCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\PickupCrateToken;
use App\Domain\ValueObject\TokenId;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class CratesService extends AbstractService
{
    private const MAX_TIME_TO_HOARD_CRATE = 'PT1H';

    public function makeNew(): void
    {
        $crateContents = $this->entityManager->getCrateTypeRepo()->getRandomCrateContents(true);

        $crate = new DbCrate(
            $crateContents->contents,
            $crateContents->value,
        );
        $crate->isGoal = $crateContents->isGoal;
        $this->entityManager->persist($crate);

        // put in a random planet
        $port = $this->entityManager->getPortRepo()->getARandomDangerousPort(Query::HYDRATE_OBJECT);

        $this->entityManager->getCrateLocationRepo()->makeInPort($crate, $port);


        $this->logger->notice(
            'Creating ' . $crateContents->contents . ' for ' . $crateContents->value . ' in ' . $port->name
        );

        $this->entityManager->flush();
    }

    /**
     * @param Port $port
     * @param User $user
     * @param int $limit
     * @return CrateLocation[]
     */
    public function findInPortForUser(Port $port, User $user, int $limit = 50): array
    {
        if ($user->getRank()->isTutorial()) {
            $results = $this->entityManager->getCrateLocationRepo()
                ->findReservedWithCrateForPortIdAndUserId(
                    $port->getId(),
                    $user->getId(),
                    $limit
                );
        } else {
            $results = $this->entityManager->getCrateLocationRepo()
                ->findWithCrateForPortId(
                    $port->getId(),
                    $limit
                );
        }

        $mapper = $this->mapperFactory->createCrateLocationMapper();
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getCrateLocation($result);
        }, $results);
    }

    /**
     * @return CrateLocation[]
     */
    public function findForShip(Ship $ship): array
    {
        $results = $this->entityManager->getCrateLocationRepo()
            ->findCurrentForShipID(
                $ship->getId()
            );

        $mapper = $this->mapperFactory->createCrateLocationMapper();
        return array_map(static function ($result) use ($mapper) {
            return $mapper->getCrateLocation($result);
        }, $results);
    }

    public function getMostRecentCrateLocationForShip(Ship $ship): ?CrateLocation
    {
        $result = $this->entityManager->getCrateLocationRepo()
            ->findMostRecentForShipID(
                $ship->getId()
            );

        if (!$result) {
            return null;
        }

        $mapper = $this->mapperFactory->createCrateLocationMapper();
        return $mapper->getCrateLocation($result);
    }

    public function crateIsInPort(UuidInterface $crateId, UuidInterface $portId): bool
    {
        return (bool)$this->entityManager->getCrateLocationRepo()
            ->findForCrateAndPortId(
                $crateId,
                $portId
            );
    }

    public function getPickupCrateToken(
        User $user,
        Crate $crate,
        Ship $ship,
        Port $port,
        UuidInterface $crateLocationId,
        string $groupKey
    ): PickupCrateToken {
        $token = $this->tokenHandler->makeToken(...PickupCrateToken::make(
            new TokenId(
                $crateLocationId,
                $this->uuidFactory->uuid5('9af42da1-6bc4-4eec-9f7e-2cdc08ff095f', $groupKey),
            ),
            $user->getId(),
            $crate->getId(),
            $port->getId(),
            $ship->getId(),
        ));
        return new PickupCrateToken(
            $token,
            TokenProvider::getActionPath(PickupCrateToken::class),
        );
    }

    public function getDropCrateToken(
        User $user,
        Crate $crate,
        Ship $ship,
        Port $port,
        string $tokenKey
    ): DropCrateToken {
        $token = $this->tokenHandler->makeToken(...DropCrateToken::make(
            new TokenId(
                $this->uuidFactory->uuid5('5b9e3fd9-a513-43a6-9678-c813793f25cd', $tokenKey),
            ),
            $user->getId(),
            $crate->getId(),
            $port->getId(),
            $ship->getId(),
        ));
        return new DropCrateToken(
            $token,
            TokenProvider::getActionPath(DropCrateToken::class),
        );
    }

    public function parsePickupCrateToken(
        string $tokenString,
        bool $confirmSingleUse = true
    ): PickupCrateToken {
        return new PickupCrateToken(
            $this->tokenHandler->parseTokenFromString($tokenString, $confirmSingleUse),
            $tokenString,
        );
    }

    public function parseDropCrateToken(
        string $tokenString,
        bool $confirmSingleUse = true
    ): DropCrateToken {
        return new DropCrateToken(
            $this->tokenHandler->parseTokenFromString($tokenString, $confirmSingleUse),
            $tokenString,
        );
    }

    public function usePickupCrateToken(
        PickupCrateToken $token
    ): void {
        $crateId = $token->getCrateId();
        $portId = $token->getPortId();
        $shipId = $token->getShipId();
        $userId = $token->getUserId();

        if (!$this->crateIsInPort($crateId, $portId)) {
            throw new OutdatedMoveException('Sorry, someone else got to that crate before you');
        }

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        /** @var DbCrate $crate */
        $crate = $this->entityManager->getCrateRepo()->getByID($crateId, Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use ($userId, $port, $crate, $token, $ship) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getCrateLocationRepo()->exitLocation($crate);

            $this->logger->info('Creating new location');
            $this->entityManager->getCrateLocationRepo()->makeInShip(
                $crate,
                $ship
            );

            $this->entityManager->getEventRepo()->logCratePickup($crate, $ship, $port);

            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($token->getOriginalToken());

            $this->logger->notice('[CRATE_PICKUP]');

            $this->entityManager->getUserAchievementRepo()->recordCratePickup($userId);
            if ($crate->isGoal) {
                $this->entityManager->getUserAchievementRepo()->recordGoalCratePickup($userId);
            }
        });
    }

    public function useDropCrateToken(
        DropCrateToken $token
    ): void {
        $crateId = $token->getCrateId();
        $portId = $token->getPortId();

        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        $crate = $this->entityManager->getCrateRepo()->getByID($crateId, Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use ($port, $crate, $token) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getCrateLocationRepo()->exitLocation($crate);

            $this->logger->info('Creating new location');
            $this->entityManager->getCrateLocationRepo()->makeInPort(
                $crate,
                $port
            );

            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($token->getOriginalToken());

            $this->logger->notice('[CRATE_DROP]');
        });
    }

    public function restoreHoardedBackToPort(
        DateTimeImmutable $now,
        int $limit
    ): int {
        $since = $now->sub(new DateInterval(self::MAX_TIME_TO_HOARD_CRATE));

        // fetch all crates that were put on a ship more than an hour ago, and that ship is still in port
        $crateLocations = $this->entityManager->getCrateLocationRepo()->getOnShipsInPortBefore(
            $since,
            $limit,
            Query::HYDRATE_OBJECT,
        );
        $total = count($crateLocations);

        // put those crates back in the port
        $this->logger->info('Putting ' . $total . ' crates back into the port');
        foreach ($crateLocations as $crateLocation) {
            /** @var DbCrateLocation $crateLocation */
            /** @var DbShipLocation $shipLocation */
            $shipLocation = $this->entityManager->getShipLocationRepo()->getCurrentForShipId(
                $crateLocation->ship->id,
                Query::HYDRATE_OBJECT
            );

            if (!$shipLocation->port) {
                throw new \LogicException('Could not obtain a port');
            }

            $this->entityManager->transactional(function () use ($crateLocation, $shipLocation) {
                $this->entityManager->getCrateLocationRepo()->exitLocation($crateLocation->crate);
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crateLocation->crate,
                    $shipLocation->port
                );
                $this->entityManager->getUserAchievementRepo()->recordHoarding($crateLocation->ship->owner->id);
            });
        }
        return $total;
    }

    public function retrieveLostCrates(): int
    {
        // find any crate locations that are current but have no ship or port. put them back in their previous port
        $crates = $this->entityManager->getCrateLocationRepo()->findLostCrates(Query::HYDRATE_OBJECT);
        $crateCount = count($crates);
        if (!$crateCount) {
            return 0;
        }
        $this->logger->notice('LOST CRATES FOUND: ' . $crateCount);
        $toSetCurrent = array_map(function (DbCrateLocation $crateLocation) {
            return $this->entityManager->getCrateLocationRepo()
                ->findPreviousForCrateId($crateLocation->crate->id, Query::HYDRATE_OBJECT);
        }, $crates);

        $this->entityManager->getConnection()->transactional(function () use ($crates, $toSetCurrent) {
            foreach ($crates as $crateLocation) {
                $crateLocation->isCurrent = false;
                $this->entityManager->persist($crateLocation);
            }
            foreach ($toSetCurrent as $oldLocation) {
                /** @var DbCrateLocation $oldLocation */
                $newLocation = new DbCrateLocation(
                    $oldLocation->crate,
                    $oldLocation->port,
                    $oldLocation->ship
                );
                $this->entityManager->persist($newLocation);
            }
            $this->entityManager->flush();
        });
        return $crateCount;
    }

    public function ensureEnoughGoalCrates(): void
    {
        // count the number of engaged users
        $userCount = $this->entityManager->getUserRepo()->countEngagedUsers();

        // count the number of goal crates
        $crateCount = $this->entityManager->getCrateRepo()->countGoalCrates();

        // if there are enough, nothing to do
        $usersPerCrate = 25;
        $expectedCount = max(1, (int)floor($userCount / $usersPerCrate));
        if ($crateCount >= $expectedCount) {
            return;
        }

        // select a non-safe port at random
        $port = $this->entityManager->getPortRepo()->getARandomDangerousPort(Query::HYDRATE_OBJECT);

        // create a new crate and put it in the port
        $this->entityManager->transactional(function () use ($port) {
            $crateContents = $this->entityManager->getCrateTypeRepo()->getGoalCrateContents();
            $crate = new DbCrate(
                $crateContents->contents,
                $crateContents->value,
            );
            $crate->isGoal = true;
            $this->entityManager->persist($crate);

            $this->entityManager->getCrateLocationRepo()->makeInPort(
                $crate,
                $port
            );
        });

        $this->logger->notice('[NEW_GOAL_CRATE] Goal Crate Created', [
            'userCount' => $userCount,
            'expectedGoalCrateCount' => $expectedCount,
            'newGoalCrateCount' => $crateCount + 1,
        ]);
    }

    public function findGoalCrateLocation(): ?Port
    {
        $location = $this->entityManager->getCrateLocationRepo()->findPortWithOldestGoalCrate();
        if ($location) {
            return $this->mapperFactory->createPortMapper()->getPort($location['port']);
        }
        return null;
    }
}
