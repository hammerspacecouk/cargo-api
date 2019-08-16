<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PurchaseShipToken extends AbstractActionToken
{
    public const KEY_SHIP_CLASS = 'spc';
    public const KEY_OWNER = 'own';

    public static function make(
        UuidInterface $ownerId,
        UuidInterface $shipClassId
    ): array {
        return parent::create([
            self::KEY_SHIP_CLASS => $shipClassId->toString(),
            self::KEY_OWNER => $ownerId->toString(),
        ]);
    }

    public function getShipClassId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_SHIP_CLASS));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_OWNER));
    }
}
