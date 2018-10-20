<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Token\Action\MoveCrate\DropCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\PickupCrateToken;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CratesService extends AbstractService
{
    public function makeNew(): void
    {
        [$contents, $value] = $this->entityManager->getDictionaryRepo()->getRandomCrateContents();
        $crate = new DbCrate(
            $contents,
            $value
        );
        $this->entityManager->persist($crate);
        $this->entityManager->flush();
    }

    public function findInPortForUser(Port $port, User $user): array
    {
        $results = $this->entityManager->getCrateLocationRepo()
            ->findWithCrateForPortIdAndUserId(
                $port->getId(),
                $user->getId()
            );

        $mapper = $this->mapperFactory->createCrateMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result['crate']);
        }, $results);
    }

    public function findForShip(Ship $ship): array
    {
        $results = $this->entityManager->getCrateLocationRepo()
            ->findCurrentForShipID(
                $ship->getId()
            );

        $mapper = $this->mapperFactory->createCrateMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result['crate']);
        }, $results);
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
        string $tokenKey
    ): PickupCrateToken {
        $token = $this->tokenHandler->makeToken(...PickupCrateToken::make(
            $this->uuidFactory->uuid5(Uuid::NIL, \sha1($tokenKey)),
            $crate->getId(),
            $port->getId(),
            $ship->getId()
        ));
        return new PickupCrateToken($token->getJsonToken(), (string)$token);
    }

    public function getDropCrateToken(
        Crate $crate,
        Ship $ship,
        Port $port,
        string $tokenKey
    ): DropCrateToken {
        $token = $this->tokenHandler->makeToken(...DropCrateToken::make(
            $this->uuidFactory->uuid5(Uuid::NIL, \sha1($tokenKey)),
            $crate->getId(),
            $port->getId(),
            $ship->getId()
        ));
        return new DropCrateToken($token->getJsonToken(), (string)$token);
    }

    public function parsePickupCrateToken(
        string $tokenString
    ): PickupCrateToken {
        return new PickupCrateToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function parseDropCrateToken(
        string $tokenString
    ): DropCrateToken {
        return new DropCrateToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function usePickupCrateToken(
        PickupCrateToken $token
    ): void {
        $crateId = $token->getCrateId();
        $portId = $token->getPortId();
        $shipId = $token->getShipId();

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        $port = $this->entityManager->getPortRepo()->getByID($portId, Query::HYDRATE_OBJECT);
        $crate = $this->entityManager->getCrateRepo()->getByID($crateId, Query::HYDRATE_OBJECT);

        $this->entityManager->transactional(function() use ($port, $crate, $token, $ship) {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getCrateLocationRepo()->exitLocation($crate);

            $this->logger->info('Creating new location');
            $this->entityManager->getCrateLocationRepo()->makeInShip(
                $crate,
                $ship
            );

            $this->entityManager->getCrateRepo()->removeReservation($crate);

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

        $this->entityManager->transactional(function() use ($port, $crate, $token) {
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

    /**
     * @param array $results
     * @return Crate[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createCrateMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getCrate($result);
        }, $results);
    }
}
