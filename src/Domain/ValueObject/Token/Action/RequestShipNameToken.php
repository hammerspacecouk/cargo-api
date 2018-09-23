<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RequestShipNameToken extends AbstractActionToken
{
    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_ID = 'ui';

    public static function make(
        UuidInterface $shipId,
        UuidInterface $userId
    ): array {
        return parent::create([
            self::KEY_SHIP_ID => (string)$shipId,
            self::KEY_USER_ID => (string)$userId,
        ]);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_SHIP_ID));
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_ID));
    }
}
