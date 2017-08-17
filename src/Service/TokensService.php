<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Token as DbToken;
use App\Data\ID;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Token\AccessToken;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TokensService extends AbstractService
{
    public function makeNewRefreshTokenCookie(string $email, string $description): Cookie
    {
        return $this->tokenHandler->makeNewRefreshTokenCookie($email, $description);
    }

    public function getAccessTokenFormRequest(Request $request): AccessToken
    {
        return $this->tokenHandler->getAccessTokenFromRequest($request);
    }

    public function getUserIdFromAccessTokenString(string $tokenString): UuidInterface
    {
        $token = $this->tokenHandler->getAccessTokenFromString($tokenString);
        return $token->getUserId();
    }

    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        bool $reverseDirection
    ): MoveShipToken {
        $token = $this->makeActionToken(MoveShipToken::makeClaims(
            $ship->getId(),
            $channel->getId(),
            $reverseDirection
        ));

        return new MoveShipToken($token);
    }

    public function getRenameShipToken(
        UuidInterface $shipId,
        string $newName
    ): RenameShipToken {
        $token = $this->tokenHandler->makeToken(
            RenameShipToken::makeClaims(
                $shipId,
                $newName
            )
        );
        return new RenameShipToken($token);
    }

    public function useRenameShipToken(
        string $token
    ): RenameShipToken {
        $token = $this->tokenHandler->parseTokenFromString($token);
        $tokenDetail = new RenameShipToken($token);
        $name = $tokenDetail->getShipName();
        $shipId = $tokenDetail->getShipId();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->getShipRepo()->renameShip($shipId, $name);
            $this->tokenHandler->markAsUsed($token);
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return $tokenDetail;
    }

    private function makeActionToken(array $claims)
    {
        return $this->tokenHandler->makeToken(
            $claims,
            ID::makeNewID(DbToken::class)
        );
    }
}
