<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class JoinConvoyToken extends AbstractActionToken
{
    private const KEY_CURRENT_SHIP_ID = 'si';
    private const KEY_OWNER_ID = 'oi';
    private const KEY_CHOSEN_SHIP_ID = 'ci';

    public static function make(
        UuidInterface $currentShipId,
        UuidInterface $ownerId,
        UuidInterface $chosenShipId
    ): array {
        return parent::create([
            self::KEY_CURRENT_SHIP_ID => $currentShipId->toString(),
            self::KEY_OWNER_ID => $ownerId->toString(),
            self::KEY_CHOSEN_SHIP_ID => $chosenShipId->toString(),
        ]);
    }

    public function getCurrentShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_CURRENT_SHIP_ID));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER_ID));
    }

    public function getChosenShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_CHOSEN_SHIP_ID));
    }
}
