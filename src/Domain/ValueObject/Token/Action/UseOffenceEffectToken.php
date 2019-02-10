<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\TokenId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UseOffenceEffectToken extends AbstractActionToken
{
    public const KEY_USER_EFFECT_ID = 'uei';
    public const KEY_DAMAGE = 'va';
    public const KEY_SHIP_ID = 'si';
    public const KEY_VICTIM_ID = 'vi';
    public const KEY_PORT_ID = 'pi';

    public static function make(
        TokenId $tokenId,
        UuidInterface $userEffectId,
        UuidInterface $playerShipId,
        UuidInterface $inPortId,
        ?int $damage,
        ?UuidInterface $victimShipId // null means applies to all
    ): array {
        return parent::create([
            self::KEY_USER_EFFECT_ID => (string)$userEffectId,
            self::KEY_SHIP_ID => (string)$playerShipId,
            self::KEY_PORT_ID => (string)$inPortId,
            self::KEY_DAMAGE => $damage,
            self::KEY_VICTIM_ID => $victimShipId ? (string)$victimShipId : null,
        ], $tokenId);
    }

    public function getVictimShipId(): ?UuidInterface
    {
        return $this->getAnOptionalId(self::KEY_VICTIM_ID);
    }

    public function getDamage(): int
    {
        return (int)$this->token->get(self::KEY_DAMAGE);
    }

    public function getPortId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_PORT_ID));
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_SHIP_ID));
    }

    public function getUserEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_EFFECT_ID));
    }
}
