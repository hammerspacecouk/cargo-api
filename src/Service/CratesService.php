<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\CrateLocation as DbCrateLocation;
use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
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
        return [];
    }

    public function moveCrateToLocation(
        UuidInterface $crateID,
        UuidInterface $locationId
    ): void {
        $crateRepo = $this->entityManager->getCrateRepo();

        // fetch the crate and the port
        $crate = $crateRepo->getByID($crateID, Query::HYDRATE_OBJECT);
        if (!$crate) {
            throw new \InvalidArgumentException('No such active crate');
        }

        $port = null;
        $ship = null;
        die('we have some work to do here. do not use this ID to decide'); // todo
//        $locationType = ID::getIDType($locationId);
//
//        switch ($locationType) {
//            case DbPort::class:
//                $port = $this->entityManager->getPortRepo()->getByID($locationId, Query::HYDRATE_OBJECT);
//                break;
//            case DbShip::class:
//                $ship = $this->entityManager->getShipRepo()->getByID($locationId, Query::HYDRATE_OBJECT);
//                break;
//        }
//
//        if (!$port && !$ship) {
//            throw new \InvalidArgumentException('Invalid destination ID');
//        }
//
//        // start the transaction
//        $this->entityManager->transactional(function () use ($crate, $port, $ship) {
//            // remove any old crate locations
//            $this->entityManager->getCrateLocationRepo()->disableAllActiveForCrateID($crate->id);
//
//            // make a new crate location
//            $newLocation = new DbCrateLocation(
//                $crate,
//                $port,
//                $ship
//            );
//
//            $this->entityManager->persist($newLocation);
//            $this->entityManager->flush();
//        });
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
