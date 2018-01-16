<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RequestShipNameToken extends AbstractActionToken
{
    public const TYPE = 'request-ship-name';

    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_ID = 'ui';

    public static function makeClaims(
        UuidInterface $shipId,
        UuidInterface $userId
    ): array {
        return parent::createClaims([
            self::KEY_SHIP_ID => (string)$shipId,
            self::KEY_USER_ID => (string)$userId,
        ]);
    }

    public function getShipId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_SHIP_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_SHIP_ID));
        }
        throw new InvalidTokenException('No Ship ID found');
    }

    public function getUserId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_USER_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_USER_ID));
        }
        throw new InvalidTokenException('No User ID found');
    }
}
