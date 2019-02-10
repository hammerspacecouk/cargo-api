<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipHealthToken extends AbstractActionToken
{
    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_ID = 'ui';
    public const KEY_PERCENT = 'pc';
    public const KEY_COST = 'co';

    public static function make(
        UuidInterface $shipId,
        UuidInterface $userId,
        int $percent,
        int $cost
    ): array {
        return parent::create([
            self::KEY_SHIP_ID => (string)$shipId,
            self::KEY_USER_ID => (string)$userId,
            self::KEY_PERCENT => $percent,
            self::KEY_COST => $cost,
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

    public function getPercent(): int
    {
        return (int)$this->token->get(self::KEY_PERCENT);
    }

    public function getCost(): int
    {
        return (int)$this->token->get(self::KEY_COST);
    }
}
