<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action\ApplyEffect;

use App\Domain\ValueObject\Token\Action\AbstractActionToken;
use App\Domain\ValueObject\TokenId;
use function App\Functions\Strings\shortHash;
use DateInterval;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

// An effect that needs no other tasks than adding to the active_effects list
class GenericApplyEffectToken extends AbstractActionToken
{
    private const KEY_EFFECT_ID = 'ei';
    private const KEY_USER_EFFECT_ID = 'ue';
    private const KEY_TRIGGERED_BY_ID = 'ti';
    private const KEY_SHIP_ID = 'si';
    private const KEY_PORT_ID = 'pi';
    private const KEY_USER_ID = 'ui';
    private const KEY_DURATION = 'du';
    private const KEY_HIT_COUNT = 'hc';

    public static function getSubject(): string
    {
        return shortHash(self::class, 8);
    }

    public static function make(
        TokenId $tokenId,
        UuidInterface $userEffectId,
        UuidInterface $effectId,
        UuidInterface $triggeredById,
        ?UuidInterface $appliesToShipId,
        ?UuidInterface $appliesToPortId,
        ?UuidInterface $appliesToUserId,
        ?int $durationSeconds = null,
        ?int $hitCount = null
    ): array {
        return parent::create([
            self::KEY_EFFECT_ID => (string)$effectId,
            self::KEY_USER_EFFECT_ID => (string)$userEffectId,
            self::KEY_TRIGGERED_BY_ID => (string)$triggeredById,
            self::KEY_SHIP_ID => $appliesToShipId ? (string)$appliesToShipId : null,
            self::KEY_PORT_ID => $appliesToPortId ? (string)$appliesToPortId : null,
            self::KEY_USER_ID => $appliesToUserId ? (string)$appliesToUserId : null,
            self::KEY_DURATION => $durationSeconds,
            self::KEY_HIT_COUNT => $hitCount,
        ], $tokenId);
    }

    public function getUserEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_EFFECT_ID));
    }

    public function getEffectId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_EFFECT_ID));
    }

    public function getTriggeredById(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_TRIGGERED_BY_ID));
    }

    public function getPortId(): ?UuidInterface
    {
        return $this->getAnOptionalId(self::KEY_PORT_ID);
    }

    public function getShipId(): ?UuidInterface
    {
        return $this->getAnOptionalId(self::KEY_SHIP_ID);
    }

    public function getUserId(): ?UuidInterface
    {
        return $this->getAnOptionalId(self::KEY_USER_ID);
    }

    public function getHitCount(): ?int
    {
        return $this->token->get(self::KEY_HIT_COUNT);
    }

    public function getDuration(): ?DateInterval
    {
        $duration = $this->token->get(self::KEY_DURATION);
        if (!empty($duration)) {
            return new DateInterval('PT' . $duration . 'S');
        }
        return $duration;
    }
}
