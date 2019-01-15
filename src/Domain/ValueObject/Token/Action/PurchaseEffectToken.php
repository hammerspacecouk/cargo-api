<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PurchaseEffectToken extends AbstractActionToken
{
    public const KEY_EFFECT = 'eff';
    public const KEY_OWNER = 'own';
    public const KEY_COST = 'cst';

    public static function make(
        UuidInterface $ownerId,
        UuidInterface $effectId,
        ?int $cost
    ): array {
        return parent::create([
            self::KEY_EFFECT => (string)$effectId,
            self::KEY_OWNER => (string)$ownerId,
            self::KEY_COST => (string)$cost,
        ]);
    }

    public function getEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_EFFECT));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_OWNER));
    }

    public function getCost(): ?int
    {
        return (int)$this->token->get(self::KEY_COST);
    }
}
