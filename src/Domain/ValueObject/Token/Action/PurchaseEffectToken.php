<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PurchaseEffectToken extends AbstractActionToken
{
    public const KEY_EFFECT = 'eff';
    public const KEY_OWNER = 'own';
    public const KEY_SHIP = 'shp';
    public const KEY_COST = 'cst';

    public static function make(
        UuidInterface $ownerId,
        UuidInterface $effectId,
        UuidInterface $shipId,
        ?int $cost
    ): array {
        return parent::create([
            self::KEY_EFFECT => $effectId->toString(),
            self::KEY_OWNER => $ownerId->toString(),
            self::KEY_SHIP => $shipId->toString(),
            self::KEY_COST => $cost,
        ]);
    }

    public function getEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_EFFECT));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER));
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP));
    }

    public function getCost(): ?int
    {
        return $this->token->claims()->get(self::KEY_COST);
    }
}
