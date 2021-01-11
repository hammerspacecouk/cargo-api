<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\Token\AbstractToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AcknowledgePromotionToken extends AbstractToken
{
    private const KEY_USER_ID = 'ui';
    private const KEY_RANK_SEEN = 'rs';
    private const KEY_AVAILABLE_CREDITS = 'ac';

    public static function make(
        UuidInterface $userId,
        UuidInterface $rankId,
        int $availableCredits
    ): array {
        return parent::create([
            self::KEY_USER_ID => $userId->toString(),
            self::KEY_RANK_SEEN => $rankId->toString(),
            self::KEY_AVAILABLE_CREDITS => $availableCredits,
        ]);
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_USER_ID));
    }

    public function getRankId(): UuidInterface
    {
        return Uuid::fromString($this->token->claims()->get(self::KEY_RANK_SEEN));
    }

    public function getAvailableCredits(): int
    {
        return $this->token->claims()->get(self::KEY_AVAILABLE_CREDITS);
    }
}
