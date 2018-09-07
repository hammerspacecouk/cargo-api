<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AcknowledgePromotionToken extends AbstractActionToken
{
    public const TYPE = 'ap';

    public const KEY_USER_ID = 'ui';
    public const KEY_RANK_SEEN = 'rs';

    public static function makeClaims(
        UuidInterface $userId,
        UuidInterface $rankId
    ): array {
        return parent::createClaims([
            self::KEY_USER_ID => (string)$userId,
            self::KEY_RANK_SEEN => (string)$rankId,
        ]);
    }

    public function getUserId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_USER_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_USER_ID));
        }
        throw new InvalidTokenException('No User ID found');
    }

    public function getRankId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_RANK_SEEN)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_RANK_SEEN));
        }
        throw new InvalidTokenException('No Rank ID found');
    }
}
