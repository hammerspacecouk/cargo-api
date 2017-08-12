<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Domain\ValueObject\Token\ShipNameToken;
use Doctrine\ORM\Query;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ActionsService extends AbstractService
{
    public function renameShip(
        ShipNameToken $token
    ): string {

        $name = $token->getShipName();
        $shipId = $token->getShipId();

        $shipRepo = $this->getShipRepo();

        /** @var DbShip $ship */
        $ship = $shipRepo->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            // update the ship name
            $ship->name = $name;
            $this->entityManager->persist($ship);

            // invalidate the token
            $this->getInvalidTokenRepo()->markAsUsed($token->getOriginalToken());

            // persist all
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return $ship->name;
    }

    public function requestShipName(
        UuidInterface $userId
    ): string {
        // todo - check there are enough credits and deduct them. throw if not
        // $this->logger->info('Deducting cost');
        return $this->getDictionaryRepo()->getRandomShipName();
    }
}