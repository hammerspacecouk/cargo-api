<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\UsedActionToken as DbToken;
use App\Data\ID;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use App\Domain\ValueObject\Token\Action\RequestShipNameToken;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;

/**
 * @deprecated - move all the things into their respective services
 */
class TokensService extends AbstractService
{


    public function getMoveShipToken(
        Ship $ship,
        Channel $channel,
        bool $reverseDirection,
        int $journeyTime,
        string $tokenKey
    ): MoveShipToken {
        $token = $this->makeActionToken(MoveShipToken::makeClaims(
            $ship->getId(),
            $channel->getId(),
            $reverseDirection,
            $journeyTime
        ), $tokenKey);

        return new MoveShipToken($token);
    }

    public function getRequestShipNameToken(
        UuidInterface $userId,
        UuidInterface $shipId
    ): RequestShipNameToken {
        $token = $this->tokenHandler->makeToken(
            RequestShipNameToken::makeClaims(
                $shipId,
                $userId
            )
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
            )
        );
        return new RenameShipToken($token);
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

    private function makeActionToken(array $claims, ?string $tokenKey = null)
    {
        if ($tokenKey) {
            $id = ID::makeIDFromKey(DbToken::class, $tokenKey);
        } else {
            $id = ID::makeNewID(DbToken::class);
        }

        return $this->tokenHandler->makeToken(
            $claims,
            $id
        );
    }







    // todo - move to shipsService
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

        // todo - adjust exit time if any abilities were applied
        $exitTime = $this->currentTime->add(
            new \DateInterval('PT' . $tokenDetail->getJourneyTime() . 'M')
        );

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

            // update the users score - todo - calculate how much the rate delta should be
            $this->entityManager->getUserRepo()->updateScore($ship->owner, 1);

            // todo - mark any abilities as used

            $this->logger->info('Marking token as used');
            $this->tokenHandler->markAsUsed($token);
            $this->logger->info('Committing transaction');
            $this->entityManager->getConnection()->commit();
            $this->logger->notice(sprintf(
                '[DEPARTURE] Ship: %s, Channel: %s, Reversed: %s',
                (string)$shipId,
                (string)$channelId,
                (string)$reversed
            ));
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Rolled back "useMoveShipToken" transaction');
            throw $e;
        }
        // todo - this return should contain the arrival time
    }
}
