<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Entity\ActiveEffect;
use App\Domain\ValueObject\TacticalEffect;
use App\Domain\ValueObject\TokenId;
use DateInterval;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MoveShipToken extends AbstractActionToken
{
    public const KEY_SHIP = 'shp';
    public const KEY_CHANNEL = 'cnl';
    public const KEY_REVERSED = 'rvsd';
    public const KEY_OWNER = 'own';
    public const KEY_JOURNEY_TIME = 'jt';
    public const KEY_EARNINGS = 'ern';
    public const KEY_EXPIRE_EFFECTS = 'eex';
    public const KEY_BREAKING_BLOCKADE = 'bb';

    public static function make(
        TokenId $tokenId,
        UuidInterface $shipId,
        UuidInterface $channelId,
        UuidInterface $ownerId,
        bool $isReversed,
        int $journeyTime,
        int $earnings,
        array $tacticalEffectsToExpire,
        bool $isBreakingBlockade
    ): array {
        return parent::create([
            self::KEY_SHIP => $shipId->toString(),
            self::KEY_CHANNEL => $channelId->toString(),
            self::KEY_OWNER => $ownerId->toString(),
            self::KEY_REVERSED => $isReversed,
            self::KEY_JOURNEY_TIME => $journeyTime,
            self::KEY_EARNINGS => $earnings,
            self::KEY_BREAKING_BLOCKADE => $isBreakingBlockade,
            self::KEY_EXPIRE_EFFECTS => \array_map(static function (TacticalEffect $tacticalEffect) {
                if ($tacticalEffect->getActiveEffect() instanceof ActiveEffect) {
                    return $tacticalEffect->getActiveEffect()->getId()->toString();
                }
                throw new \RuntimeException('An active tactical effect without an active effect?');
            }, $tacticalEffectsToExpire),
        ], $tokenId);
    }

    public function getShipId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_SHIP));
    }

    public function getChannelId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_CHANNEL));
    }

    public function getOwnerId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_OWNER));
    }

    public function isReversed(): bool
    {
        return $this->token->claims()->get(self::KEY_REVERSED);
    }

    public function isBreakingBlockade(): bool
    {
        return $this->token->claims()->get(self::KEY_BREAKING_BLOCKADE);
    }

    public function getJourneyTime(): DateInterval
    {
        return new DateInterval('PT' . $this->token->claims()->get(self::KEY_JOURNEY_TIME) . 'S');
    }

    public function getEarnings(): int
    {
        return $this->token->claims()->get(self::KEY_EARNINGS);
    }

    /**
     * @return UuidInterface[]
     */
    public function getEffectIdsToExpire(): array
    {
        return \array_map(function (string $id) {
            return Uuid::fromString($id);
        }, $this->token->claims()->get(self::KEY_EXPIRE_EFFECTS));
    }
}
