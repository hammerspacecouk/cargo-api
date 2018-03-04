<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DeleteAccountToken extends AbstractToken
{
    public const TOKEN_TIME = 'PT15M';

    public const TYPE = 'da';

    public const KEY_USER_ID = 'ui';
    public const KEY_STAGE = 'st';

    public static function makeClaims(
        UuidInterface $userId,
        int $stage
    ): array {
        return parent::createClaims([
            self::KEY_USER_ID => (string) $userId,
            self::KEY_STAGE => $stage,
        ]);
    }

    public function getUserId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_USER_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_USER_ID));
        }
        throw new InvalidTokenException('No User ID found');
    }

    public function getStage(): int
    {
        if ($this->token->hasClaim(self::KEY_STAGE)) {
            return $this->token->getClaim(self::KEY_STAGE);
        }
        throw new InvalidTokenException('No stage found');
    }
}
