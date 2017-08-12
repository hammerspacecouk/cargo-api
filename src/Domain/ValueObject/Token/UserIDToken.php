<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserIDToken extends AbstractToken
{
    protected const TYPE = 'ui';

    private const KEY_UUID = 'u';

    public function getUuid(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_UUID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_UUID));
        }
        throw new InvalidTokenException('No ID found');
    }

    public static function makeClaims(UuidInterface $userID): array
    {
        return parent::createClaims([
            self::KEY_UUID => $userID,
        ]);
    }
}