<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\TokenId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SellShipToken extends AbstractActionToken
{
    private const KEY_SHIP_ID = 'si';
    private const KEY_OWNER_ID = 'oi';
    private const KEY_EARNING = 'ern';

    public static function make(
        UuidInterface $shipId,
        UuidInterface $ownerId,
        int $earnings
    ): array {
        return parent::create([
            self::KEY_SHIP_ID => $shipId->toString(),
            self::KEY_OWNER_ID => $ownerId->toString(),
            self::KEY_EARNING => $earnings,
        ], new TokenId($shipId));
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP_ID));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER_ID));
    }

    public function getEarnings(): int
    {
        return (int)$this->token->claims()->get(self::KEY_EARNING);
    }
}
