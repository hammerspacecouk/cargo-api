<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AcknowledgePromotionToken extends AbstractActionToken
{
    private const KEY_USER_ID = 'ui';
    private const KEY_RANK_SEEN = 'rs';

    public static function make(
        UuidInterface $userId,
        UuidInterface $rankId
    ): array {
        return parent::create([
            self::KEY_USER_ID => $userId->toString(),
            self::KEY_RANK_SEEN => $rankId->toString(),
        ]);
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_ID));
    }

    public function getRankId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_RANK_SEEN));
    }
}
