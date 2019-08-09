<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RemoveAuthProviderToken extends AbstractActionToken
{
    public const KEY_USER_ID = 'ui';
    public const KEY_PROVIDER = 'op';

    public static function make(
        UuidInterface $userId,
        string $authProvider
    ): array {
        return parent::create([
            self::KEY_USER_ID => $userId->toString(),
            self::KEY_PROVIDER => $authProvider,
        ]);
    }

    public function getAuthProvider(): int
    {
        return $this->token->get(self::KEY_PROVIDER);
    }

    public function getUserId(): UuidInterface
    {
        return Uuid::fromString($this->token->get(self::KEY_USER_ID));
    }
}
