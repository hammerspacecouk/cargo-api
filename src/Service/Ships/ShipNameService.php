<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\Database\Entity\User;
use App\Domain\Exception\IllegalMoveException;
use App\Domain\ValueObject\Costs;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use App\Domain\ValueObject\Token\Action\RequestShipNameToken;
use App\Domain\ValueObject\Transaction;
use App\Service\ShipsService;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

class ShipNameService extends ShipsService
{
    public function getRequestShipNameTransaction(
        UuidInterface $userId,
        UuidInterface $shipId
    ): Transaction {
        $token = $this->tokenHandler->makeToken(...RequestShipNameToken::make(
            $shipId,
            $userId
        ));
        return new Transaction(
            Costs::ACTION_REQUEST_SHIP_NAME,
            new RequestShipNameToken($token->getJsonToken(), (string)$token)
        );
    }

    public function getRenameShipToken(
        UuidInterface $shipId,
        string $newName
    ): RenameShipToken {
        $token = $this->tokenHandler->makeToken(...RenameShipToken::make(
            $shipId,
            $newName
        ));
        return new RenameShipToken($token->getJsonToken(), (string)$token);
    }

    // Parse tokens

    public function parseRenameShipToken(
        string $tokenString
    ): RenameShipToken {
        return new RenameShipToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function parseRequestShipNameToken(
        string $tokenString
    ): RequestShipNameToken {
        return new RequestShipNameToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useRequestShipNameToken(
        RequestShipNameToken $token
    ): string {
        $name = $this->requestShipName($token->getUserId(), $token->getShipId());
        $this->tokenHandler->markAsUsed($token->getOriginalToken());
        return $name;
    }

    public function useRenameShipToken(
        RenameShipToken $tokenDetail
    ): void {
        $name = $tokenDetail->getShipName();
        $shipId = $tokenDetail->getShipId();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->logger->info('Renaming ship');
            $this->entityManager->getShipRepo()->renameShip($shipId, $name);
            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($tokenDetail->getOriginalToken());
            $this->logger->info('Committing transaction');
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back "useRenameShipToken" transaction');
            throw $e;
        }
    }

    private function requestShipName(
        UuidInterface $userId,
        UuidInterface $shipId
    ): string {

        // check the ship exists and belongs to the user
        if (!$this->entityManager->getShipRepo()->getShipForOwnerId($shipId, $userId)) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        $userRepo = $this->entityManager->getUserRepo();

        // check the user has enough credits
        $userEntity = $userRepo->getByID($userId, Query::HYDRATE_OBJECT);
        /** @var User $userEntity * */
        if ($userRepo->currentScore($userEntity) < Costs::ACTION_REQUEST_SHIP_NAME) {
            throw new IllegalMoveException(Costs::ACTION_REQUEST_SHIP_NAME . ' required to request a ship name');
        }

        $userRepo->updateScoreValue($userEntity, -Costs::ACTION_REQUEST_SHIP_NAME);

        return $this->entityManager->getDictionaryRepo()->getRandomShipName();
    }
}
