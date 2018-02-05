<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\Mapper\UserMapper;
use App\Data\ID;
use App\Domain\Entity\User;
use App\Domain\ValueObject\EmailAddress;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class UsersService extends AbstractService
{
    private $userMapper;

    public function getById(
        UuidInterface $uuid
    ): ?User {
        return $this->mapSingle(
            $this->entityManager->getUserRepo()->getById($uuid)
        );
    }

    public function getOrCreateByEmailAddress(EmailAddress $email): ?User
    {
        $userRepo = $this->entityManager->getUserRepo();
        $emailAddress = (string) $email;
        $userEntity = $userRepo->getByEmail($emailAddress);
        if (!$userEntity) {
            $this->logger->notice('[NEW PLAYER] Creating a new player');
            $userEntity = $userRepo->createByEmail($emailAddress);
        }
        return $this->mapSingle($userEntity);
    }

    public function startPlayer(UuidInterface $userId): void
    {
        /** @var DbUser $dbUser */
        $dbUser = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);
        if (!$dbUser) {
            throw new \InvalidArgumentException('No such player exists');
        }

        $safeHaven = $this->entityManager->getPortRepo()->getARandomSafePort(Query::HYDRATE_OBJECT);
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();

        // start a transaction
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Set the users original home port
            $dbUser->homePort = $safeHaven;

            // Make a new ship
            $ship = new DbShip(
                ID::makeNewID(DbShip::class),
                $shipName,
                $starterShipClass,
                $dbUser
            );

            // Put the ship into the home port
            $location = new DbShipLocation(
                ID::makeNewID(DbShipLocation::class),
                $ship,
                $safeHaven,
                null,
                $this->currentTime
            );

            // Activate two crates - todo

            // Put crate 1 into the port - todo

            // Put crate 2 onto the ship - todo

            // Save everything
            $this->entityManager->persist($ship);
            $this->entityManager->persist($location);
            $this->entityManager->persist($dbUser);
            $this->entityManager->flush();

            // end the transaction

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    private function getMapper(): UserMapper
    {
        if (!$this->userMapper) {
            $this->userMapper = $this->mapperFactory->createUserMapper();
        }
        return $this->userMapper;
    }

    private function mapSingle(?array $result): ?User
    {
        if (!$result) {
            return null;
        }
        return $this->getMapper()->getUser($result);
    }

    /**
     * @return User[]
     */
    private function mapMany(array $results): array
    {
        return array_map(['self', 'mapSingle'], $results);
    }
}
