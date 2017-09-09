<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AccessToken extends AbstractToken
{
    public const TYPE = 'a';

    public const KEY_USER_ID = 'u';

    private $cookies = [];

    public function __construct(Token $token, array $cookies = [])
    {
        parent::__construct($token);
        $this->cookies = $cookies;
    }

    public static function makeClaims(UuidInterface $userID): array
    {
        return parent::createClaims([
            self::KEY_USER_ID => (string)$userID,
        ]);
    }

    public function getUserId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_USER_ID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_USER_ID));
        }
        throw new InvalidTokenException('No ID found');
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }
}
