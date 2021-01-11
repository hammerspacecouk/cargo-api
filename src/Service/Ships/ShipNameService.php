<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Data\TokenProvider;
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
            $userId,
        ));
        return new Transaction(
            Costs::ACTION_REQUEST_SHIP_NAME,
            new RequestShipNameToken(
                $token,
                TokenProvider::getActionPath(RequestShipNameToken::class),
            ),
        );
    }

    public function getRenameShipToken(
        UuidInterface $userId,
        UuidInterface $shipId,
        string $newName
    ): RenameShipToken {
        $token = $this->tokenHandler->makeToken(...RenameShipToken::make(
            $userId,
            $shipId,
            $newName,
        ));
        return new RenameShipToken(
            $token,
            TokenProvider::getActionPath(RenameShipToken::class),
        );
    }

    // Parse tokens

    public function parseRenameShipToken(
        string $tokenString
    ): RenameShipToken {
        return new RenameShipToken(
            $this->tokenHandler->parseTokenFromString($tokenString),
            $tokenString,
        );
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
        $userId = $tokenDetail->getUserId();

        $this->entityManager->getConnection()->transactional(function () use ($shipId, $name, $tokenDetail, $userId) {
            $this->entityManager->getShipRepo()->renameShip($shipId, $name);
            $this->tokenHandler->markAsUsed($tokenDetail->getOriginalToken());

            $this->entityManager->getUserAchievementRepo()->recordRenameShip($userId);
        });
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
        $userEntity = $userRepo->getByID($userId, Query::HYDRATE_OBJECT);
        $this->consumeCredits($userEntity, Costs::ACTION_REQUEST_SHIP_NAME);

        return $this->entityManager->getDictionaryRepo()->getRandomShipName();
    }
}
