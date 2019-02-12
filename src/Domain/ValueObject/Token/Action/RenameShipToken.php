<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RenameShipToken extends AbstractActionToken
{
    public const KEY_SHIP_NAME = 'sn';
    public const KEY_SHIP_ID = 'si';

    public static function make(
        UuidInterface $shipId,
        string $shipName
    ): array {
        return parent::create([
            self::KEY_SHIP_NAME => $shipName,
            self::KEY_SHIP_ID => $shipId->toString(),
        ]);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_SHIP_ID));
    }

    public function getShipName(): string
    {
        return $this->token->get(self::KEY_SHIP_NAME);
    }
}
