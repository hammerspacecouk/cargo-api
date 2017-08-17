<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RenameShipToken extends AbstractActionToken
{
    protected const TYPE = 'rename-ship';

    private const KEY_SHIP_NAME = 'sn';
    private const KEY_SHIP_ID = 'si';

    public function getShipId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_SHIP_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_SHIP_ID));
        }
        throw new InvalidTokenException('No Ship ID found');
    }

    public function getShipName(): string
    {
        if ($this->token->hasClaim(self::KEY_SHIP_NAME)) {
            return $this->token->getClaim(self::KEY_SHIP_NAME);
        }
        throw new InvalidTokenException('No Ship Name found');
    }

    public static function makeClaims(
        UuidInterface $shipId,
        string $shipName
    ): array {
        return parent::createClaims([
            self::KEY_SHIP_NAME => $shipName,
            self::KEY_SHIP_ID => (string) $shipId
        ]);
    }
}
