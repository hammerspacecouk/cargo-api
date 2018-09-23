<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MoveShipToken extends AbstractActionToken
{
    public const KEY_SHIP = 'shp';
    public const KEY_CHANNEL = 'cnl';
    public const KEY_REVERSED = 'rvsd';
    public const KEY_OWNER = 'own';
    public const KEY_JOURNEY_TIME = 'jt';

    public static function make(
        UuidInterface $tokenId,
        UuidInterface $shipId,
        UuidInterface $channelId,
        UuidInterface $ownerId,
        bool $isReversed,
        int $journeyTime
    ): array {
        return parent::create([
            self::KEY_SHIP => (string)$shipId,
            self::KEY_CHANNEL => (string)$channelId,
            self::KEY_OWNER => (string)$ownerId,
            self::KEY_REVERSED => $isReversed,
            self::KEY_JOURNEY_TIME => $journeyTime,
        ], $tokenId);
    }

    public function getShipId(): UuidInterface
    {
            return Uuid::fromString($this->token->get(self::KEY_SHIP));
    }

    public function getChannelId(): UuidInterface
    {
            return Uuid::fromString($this->token->get(self::KEY_CHANNEL));
    }

    public function getOwnerId(): UuidInterface
    {
            return Uuid::fromString($this->token->get(self::KEY_OWNER));
    }

    public function isReversed(): bool
    {
            return $this->token->get(self::KEY_REVERSED);
    }

    public function getJourneyTime(): int
    {
            return $this->token->get(self::KEY_JOURNEY_TIME);
    }
}
