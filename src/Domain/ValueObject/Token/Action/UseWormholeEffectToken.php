<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\TokenId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UseWormholeEffectToken extends AbstractActionToken
{
    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_EFFECT_ID = 'uei';
    public const KEY_DESTINATION_ID = 'di';

    public static function make(
        TokenId $tokenId,
        UuidInterface $userEffectId,
        UuidInterface $shipId,
        UuidInterface $destinationId
    ): array {
        return parent::create([
            self::KEY_USER_EFFECT_ID => $userEffectId->toString(),
            self::KEY_SHIP_ID => $shipId->toString(),
            self::KEY_DESTINATION_ID => $destinationId->toString(),
        ], $tokenId);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP_ID));
    }

    public function getDestinationId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_DESTINATION_ID));
    }

    public function getUserEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_USER_EFFECT_ID));
    }
}
