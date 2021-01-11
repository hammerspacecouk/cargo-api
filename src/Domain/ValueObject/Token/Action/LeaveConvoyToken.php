<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LeaveConvoyToken extends AbstractActionToken
{
    private const KEY_CURRENT_SHIP_ID = 'si';
    private const KEY_OWNER_ID = 'oi';

    public static function make(
        UuidInterface $currentShipId,
        UuidInterface $ownerId
    ): array {
        return parent::create([
            self::KEY_CURRENT_SHIP_ID => $currentShipId->toString(),
            self::KEY_OWNER_ID => $ownerId->toString(),
        ]);
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER_ID));
    }

    public function getCurrentShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_CURRENT_SHIP_ID));
    }
}
