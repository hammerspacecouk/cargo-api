<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action\MoveCrate;

use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Domain\ValueObject\TokenId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractMoveCrateToken extends AbstractActionToken
{
    private const KEY_CRATE_ID = 'ci';
    private const KEY_PORT_ID = 'pi';
    private const KEY_SHIP_ID = 'si';

    public static function make(
        TokenId $tokenId,
        UuidInterface $crateId,
        UuidInterface $portId,
        UuidInterface $shipId
    ): array {
        return parent::create([
            self::KEY_CRATE_ID => $crateId->toString(),
            self::KEY_PORT_ID => $portId->toString(),
            self::KEY_SHIP_ID => $shipId->toString(),
        ], $tokenId);
    }

    public function getCrateId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_CRATE_ID));
    }

    public function getPortId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_PORT_ID));
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_SHIP_ID));
    }
}
