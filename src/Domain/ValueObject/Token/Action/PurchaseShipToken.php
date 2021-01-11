<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\TokenId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PurchaseShipToken extends AbstractActionToken
{
    public const KEY_SHIP_CLASS = 'spc';
    public const KEY_OWNER = 'own';
    public const KEY_COST = 'cst';

    public static function make(
        UuidInterface $ownerId,
        UuidInterface $shipClassId,
        int $cost,
        array $allFleetIds
    ): array {
        $fleetId = Uuid::uuid5(
            'eda687a7-0c05-4ecf-8902-c66b2e890bbe',
            json_encode($allFleetIds, JSON_THROW_ON_ERROR, 512)
        );
        return parent::create([
            self::KEY_SHIP_CLASS => $shipClassId->toString(),
            self::KEY_OWNER => $ownerId->toString(),
            self::KEY_COST => $cost,
        ], new TokenId($fleetId));
    }

    public function getShipClassId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP_CLASS));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER));
    }

    public function getCost(): int
    {
        return (int)$this->token->claims()->get(self::KEY_COST);
    }
}
