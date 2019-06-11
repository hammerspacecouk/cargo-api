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
        $crateContents = $this->entityManager->getCrateTypeRepo()->getRandomCrateContents();
        $crate = new DbCrate(
            $crateContents->contents,
            $crateContents->value,
        );
        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function findInPortForUser(Port $port, User $user, $limit = 50): array
    {
        $results = $this->entityManager->getCrateLocationRepo()
            ->findWithCrateForPortIdAndUserId(
                $port->getId(),
                $user->getId(),
                $limit
            );

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
            $crate->getId(),
            $port->getId(),
            $ship->getId(),
        ));
        return new PickupCrateToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(PickupCrateToken::class, $this->dateTimeFactory->now()),
        );
    }

    public function getDropCrateToken(
        Crate $crate,
        Ship $ship,
        Port $port,
        string $tokenKey
    ): DropCrateToken {
        $token = $this->tokenHandler->makeToken(...DropCrateToken::make(
            new TokenId(
                $this->uuidFactory->uuid5('5b9e3fd9-a513-43a6-9678-c813793f25cd', $tokenKey),
            ),
            $crate->getId(),
            $port->getId(),
            $ship->getId(),
        ));
        return new DropCrateToken(
            $token->getJsonToken(),
            (string)$token,
            TokenProvider::getActionPath(DropCrateToken::class, $this->dateTimeFactory->now()),
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

        if (!$this->crateIsInPort($crateId, $portId)) {
            throw new OutdatedMoveException('Sorry, someone else got to that crate before you');
        }

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        $crate = $this->entityManager->getCrateRepo()->getByID($crateId, Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function () use ($port, $crate, $token, $ship) {
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

            // todo - event log should cover hoarding
            $this->entityManager->transactional(function () use ($crateLocation, $shipLocation) {
                $this->entityManager->getCrateLocationRepo()->exitLocation($crateLocation->crate);
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crateLocation->crate,
                    $shipLocation->port
                );
            });
        }
        return $total;
    }
}
