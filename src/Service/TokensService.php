<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Token as DbToken;
use App\Data\ID;
use App\Data\TokenHandler;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Token\AccessToken;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use App\Domain\ValueObject\Token\EmailLoginToken;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TokensService extends AbstractService
{
    public function makeNewRefreshTokenCookie(string $email, string $description): Cookie
    {
        return $this->tokenHandler->makeNewRefreshTokenCookie($email, $description);
    }

    public function getAccessTokenFromRequest(Request $request): AccessToken
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

    public function getEmailLoginToken(
        string $emailAddress
    ): EmailLoginToken {
        $token = $this->tokenHandler->makeToken(
            EmailLoginToken::makeClaims(
                $emailAddress
            ),
            null,
            TokenHandler::EXPIRY_EMAIL_LOGIN
        );
        return new EmailLoginToken($token);
    }


    // Parse tokens
    public function parseEmailLoginToken(
        string $tokenString
    ): EmailLoginToken {
        return $this->parseToken($tokenString, EmailLoginToken::class, false);
    }

    public function parseRenameShipToken(
        string $tokenString
    ): RenameShipToken {
        return $this->parseToken($tokenString, RenameShipToken::class);
    }






    public function useMoveShipToken(
        string $token
    ): void {
        $token = $this->tokenHandler->parseTokenFromString($token);
        $tokenDetail = new MoveShipToken($token);

        $shipId = $tokenDetail->getShipId();
        $channelId = $tokenDetail->getChannelId();
        $reversed = $tokenDetail->isReversed();

        $ship = $this->entityManager->getShipRepo()->getByID($shipId, Query::HYDRATE_OBJECT);
        if (!$ship) {
            throw new \InvalidArgumentException('No such ship');
        }

        $channel = $this->entityManager->getChannelRepo()->getByID($channelId, Query::HYDRATE_OBJECT);
        if (!$channel) {
            throw new \InvalidArgumentException('No such channel');
        }

        // todo - calculate a real exit time, taking into account any active abilities
        $exitTime = $this->currentTime->add(new \DateInterval('PT1H'));

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->logger->info('Revoking previous location');
            $this->entityManager->getShipLocationRepo()->exitLocation($ship);

            $this->logger->info('Creating new location');
            $this->entityManager->getShipLocationRepo()->makeInChannel(
                $ship,
                $channel,
                $exitTime,
                $reversed
            );

            // todo - mark any abilities as used

            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($token);
            $this->logger->info('Committing transaction');
            $this->entityManager->getConnection()->commit();
            $this->logger->notice(sprintf(
                '[MOVE SHIP] Ship: %s, Channel: %s, Reversed: %s',
                (string) $shipId,
                (string) $channelId,
                (string) $reversed
            ));
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back "useMoveShipToken" transaction');
            throw $e;
        }
        // todo - this return should contain the arrival time
    }

    private function makeActionToken(array $claims)
    {
        return $this->tokenHandler->makeToken(
            $claims,
            ID::makeNewID(DbToken::class)
        );
    }

    private function parseToken(string $tokenString, string $class, $checkIfInvalidated = true)
    {
        $token = $this->tokenHandler->parseTokenFromString($tokenString, $checkIfInvalidated);
        return new $class($token);
    }
}
