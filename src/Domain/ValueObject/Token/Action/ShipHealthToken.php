<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShipHealthToken extends AbstractActionToken
{
    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_ID = 'ui';
    public const KEY_AMOUNT = 'am';
    public const KEY_COST = 'co';

    public static function make(
        UuidInterface $shipId,
        UuidInterface $userId,
        int $amount,
        int $cost
    ): array {
        return parent::create([
            self::KEY_SHIP_ID => $shipId->toString(),
            self::KEY_USER_ID => $userId->toString(),
            self::KEY_AMOUNT => $amount,
            self::KEY_COST => $cost,
        ]);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP_ID));
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_USER_ID));
    }

    public function getAmount(): int
    {
        return (int)$this->token->claims()->get(self::KEY_AMOUNT);
    }

    public function getCost(): int
    {
        return (int)$this->token->claims()->get(self::KEY_COST);
    }
}
