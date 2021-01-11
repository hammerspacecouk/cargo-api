<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RenameShipToken extends AbstractActionToken
{
    public const KEY_SHIP_NAME = 'sn';
    public const KEY_SHIP_ID = 'si';
    public const KEY_USER_ID = 'ui';

    public static function make(
        UuidInterface $userId,
        UuidInterface $shipId,
        string $shipName
    ): array {
        return parent::create([
            self::KEY_SHIP_NAME => $shipName,
            self::KEY_SHIP_ID => $shipId->toString(),
            self::KEY_USER_ID => $userId->toString(),
        ]);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP_ID));
    }

    public function getShipName(): string
    {
        return $this->token->claims()->get(self::KEY_SHIP_NAME);
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_USER_ID));
    }
}
