<?php
declare(strict_types = 1);
namespace App\Service;

use App\Data\Database\Entity\Token as DbToken;
use App\Data\ID;
use App\Domain\ValueObject\Token\AccessToken;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TokensService extends AbstractService
{
    public function makeNewRefreshTokenCookie(string $email, string $description): Cookie
    {
        return $this->tokenHandler->makeNewRefreshTokenCookie($email, $description);
    }

    public function getAccessTokenFormRequest(Request $request): AccessToken
    {
        return $this->tokenHandler->getAccessTokenFromRequest($request);
    }

    public function getUserIdFromAccessTokenString(string $tokenString): UuidInterface
    {
        $token = $this->tokenHandler->getAccessTokenFromString($tokenString);
        return $token->getUserId();
    }

    public function parseActionTokenFromString(string $token, string $expectedClass)
    {
        
    }

    public function makeActionToken(array $claims)
    {
        return $this->tokenHandler->makeToken(
            $claims,
            ID::makeNewID(DbToken::class)
        );
    }
}
