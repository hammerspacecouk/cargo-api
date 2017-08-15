<?php
declare(strict_types = 1);
namespace App\Service;

use App\Domain\ValueObject\Token\AbstractToken;
use App\Domain\ValueObject\Token\AccessToken;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TokensService extends AbstractService
{
    public function makeNewRefreshTokenCookie(string $email, string $description): Cookie
    {
        return $this->tokenHandler->makeNewRefreshTokenCookie($email, $description);
    }

    public function getAccessToken(Request $request): AccessToken
    {
        return $this->tokenHandler->getAccessToken($request);
    }
}
