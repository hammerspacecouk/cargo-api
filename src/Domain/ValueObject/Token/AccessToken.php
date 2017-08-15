<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;

class AccessToken extends AbstractToken
{
    protected const TYPE = 'ui';

    private const KEY_UUID = 'u';

    private $cookies = [];

    public function __construct(Token $token, array $cookies = [])
    {
        parent::__construct($token);
        $this->cookies = $cookies;
    }

    public function getUserId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_UUID)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_UUID));
        }
        throw new InvalidTokenException('No ID found');
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public static function makeClaims(UuidInterface $userID): array
    {
        return parent::createClaims([
            self::KEY_UUID => $userID,
        ]);
    }
}