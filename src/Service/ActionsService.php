<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Ship as DbShip;
use App\Domain\ValueObject\Token\ShipNameToken;
use App\Domain\ValueObject\Token\AccessToken;
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
            $this->tokenHandler->markAsUsed($token->getOriginalToken());

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
        UuidInterface $userId,
        UuidInterface $shipId
    ): ShipNameToken {

        // check the ship exists and belongs to the user
        if (!$this->getShipRepo()->getShipForOwnerId($shipId, $userId)) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        // todo - check the user has enough credits

        // todo -deduct the user credits

        $name = $this->getDictionaryRepo()->getRandomShipName();

        $token = $this->tokenHandler->makeToken(
            ShipNameToken::makeClaims(
                $shipId,
                $name
            )
        );

        return new ShipNameToken($token);
    }
}