<?php
declare(strict_types=1);

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
    public const KEY_OWNER = 'own';
    public const KEY_JOURNEY_TIME = 'jt';

    public function __construct(Token $token)
    {
        parent::__construct($token);
    }

    public static function makeClaims(
        UuidInterface $shipId,
        UuidInterface $channelId,
        UuidInterface $ownerId,
        bool $isReversed,
        int $journeyTime
    ): array {
        return parent::createClaims([
            self::KEY_SHIP => (string)$shipId,
            self::KEY_CHANNEL => (string)$channelId,
            self::KEY_OWNER => (string)$ownerId,
            self::KEY_REVERSED => $isReversed,
            self::KEY_JOURNEY_TIME => $journeyTime,
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

    public function getOwnerId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_OWNER)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_OWNER));
        }
        throw new InvalidTokenException('No Owner ID found');
    }

    public function isReversed(): bool
    {
        if ($this->token->hasClaim(self::KEY_REVERSED)) {
            return $this->token->getClaim(self::KEY_REVERSED);
        }
        throw new InvalidTokenException('No Reverse value found');
    }

    public function getJourneyTime(): int
    {
        if ($this->token->hasClaim(self::KEY_JOURNEY_TIME)) {
            return $this->token->getClaim(self::KEY_JOURNEY_TIME);
        }
        throw new InvalidTokenException('No Journey time found');
    }
}
