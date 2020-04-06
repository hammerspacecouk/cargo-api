<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LeaveConvoyToken extends AbstractActionToken
{
    private const KEY_CURRENT_SHIP_ID = 'si';

    public static function make(
        UuidInterface $currentShipId
    ): array {
        return parent::create([
            self::KEY_CURRENT_SHIP_ID => $currentShipId->toString(),
        ]);
    }

    public function getCurrentShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_CURRENT_SHIP_ID));
    }
}
