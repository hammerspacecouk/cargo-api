<?php declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MoveShipToken extends AbstractActionToken
{
    public const TYPE = 'move-ship';

    public const KEY_SHIP = 'shp';
    public const KEY_CHANNEL = 'cnl';
    public const KEY_REVERSED = 'rvsd';

    public function __construct(Token $token)
    {
        parent::__construct($token);
    }

    public static function makeClaims(
        UuidInterface $shipId,
        UuidInterface $channelId,
        bool $isReversed
    ): array {
        return parent::createClaims([
            self::KEY_SHIP => (string)$shipId,
            self::KEY_CHANNEL => (string)$channelId,
            self::KEY_REVERSED => $isReversed,
        ]);
    }

    public function getShipId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_SHIP)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_SHIP));
        }
        throw new InvalidTokenException('No Ship ID found');
    }

    public function getChannelId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_CHANNEL)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_CHANNEL));
        }
        throw new InvalidTokenException('No Channel ID found');
    }

    public function isReversed(): bool
    {
        if ($this->token->hasClaim(self::KEY_REVERSED)) {
            return $this->token->getClaim(self::KEY_REVERSED);
        }
        throw new InvalidTokenException('No Channel ID found');
    }
}
