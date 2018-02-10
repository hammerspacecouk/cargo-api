<?php
declare(strict_types=1);

namespace App\Service\Ships;

use App\Domain\ValueObject\Token\Action\RenameShipToken;
use App\Domain\ValueObject\Token\Action\RequestShipNameToken;
use App\Service\ShipsService;
use Ramsey\Uuid\UuidInterface;

class ShipNameService extends ShipsService
{
    private const TOKEN_EXPIRY = 'PT1H';

    public function getRequestShipNameToken(
        UuidInterface $userId,
        UuidInterface $shipId
    ): RequestShipNameToken {
        $token = $this->tokenHandler->makeToken(
            RequestShipNameToken::makeClaims(
                $shipId,
                $userId
            ),
            self::TOKEN_EXPIRY
        );
        return new RequestShipNameToken($token);
    }

    public function getRenameShipToken(
        UuidInterface $shipId,
        string $newName
    ): RenameShipToken {
        $token = $this->tokenHandler->makeToken(
            RenameShipToken::makeClaims(
                $shipId,
                $newName
            ),
            self::TOKEN_EXPIRY
        );
        return new RenameShipToken($token);
    }


    public function requestShipName(
        UuidInterface $userId,
        UuidInterface $shipId
    ): string {

        // check the ship exists and belongs to the user
        if (!$this->entityManager->getShipRepo()->getShipForOwnerId($shipId, $userId)) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        // todo - check the user has enough credits

        // todo -deduct the user credits

        // todo - should it check to see if it already exists?

        return $this->entityManager->getDictionaryRepo()->getRandomShipName();
    }


    // Parse tokens

    public function parseRenameShipToken(
        string $tokenString
    ): RenameShipToken {
        return new RenameShipToken($this->tokenHandler->parseTokenFromString($tokenString));
    }

    public function parseRequestShipNameToken(
        string $tokenString
    ): RequestShipNameToken {
        return new RequestShipNameToken($this->tokenHandler->parseTokenFromString($tokenString));
    }

    public function useRequestShipNameToken(
        RequestShipNameToken $token
    ) {
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
            $this->logger->notice('[SHIP RENAME] Ship ' . (string)$shipId . ' renamed to ' . $name);
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back "useRenameShipToken" transaction');
            throw $e;
        }
    }
}
