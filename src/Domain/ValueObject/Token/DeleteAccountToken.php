<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DeleteAccountToken extends AbstractToken
{
    public const EXPIRY = 'PT15M';

    private const KEY_USER_ID = 'ui';
    private const KEY_STAGE = 'st';

    public static function make(
        UuidInterface $userId,
        int $stage
    ): array {
        return parent::create([
            self::KEY_USER_ID => $userId->toString(),
            self::KEY_STAGE => $stage,
        ]);
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_ID));
    }

    public function getStage(): int
    {
        return $this->token->get(self::KEY_STAGE);
    }
}
