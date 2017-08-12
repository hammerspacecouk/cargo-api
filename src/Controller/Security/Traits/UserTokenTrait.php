<?php
declare(strict_types = 1);
namespace App\Controller\Security\Traits;

use App\ApplicationTime;
use App\Config\TokenConfig;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\UserIDToken;
use App\Service\TokensService;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait UserTokenTrait
{
    protected function getUserId(
        Request $request,
        TokenConfig $tokenConfig,
        TokensService $tokensService,
        bool $checkInvalidTokens = true
    ): UuidInterface {
        $tokenString = $request->cookies->get($tokenConfig->getCookieName());

        // todo - also possible to get it out of the Auth header
        if (!$tokenString) {
            throw new BadRequestHttpException('No credentials');
        }

        try {
            $token = $tokensService->parseTokenFromString($tokenString, $checkInvalidTokens);
            $userIdToken = new UserIDToken($token);
        } catch (InvalidTokenException | InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }

        return $userIdToken->getUuid();
    }

    protected function getUserIdReadOnly(
        Request $request,
        TokenConfig $tokenConfig,
        TokensService $tokensService
    ): UuidInterface {
        return $this->getUserId($request, $tokenConfig, $tokensService, false);
    }

    protected function makeCookieForWebToken(TokenConfig $tokenConfig, Token $token): Cookie
    {
        $secureCookie = false; // todo - be true as often as possible
        return new Cookie(
            $tokenConfig->getCookieName(),
            (string) $token,
            ApplicationTime::getTime()->add(new \DateInterval('P2Y')), // todo - check interval/session based etc
            '/',
            null,
            $secureCookie,
            true
        );
    }
}
