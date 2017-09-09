<?php
declare(strict_types=1);

namespace App\Controller\Security\Traits;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\MissingTokenException;
use App\Service\TokensService;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait UserTokenTrait
{
    protected $cookies = [];

    protected function getUserId(
        Request $request,
        TokensService $tokensService
    ): UuidInterface {

        try {
            // try and get back a userId
            $token = $tokensService->getAccessTokenFromRequest($request);

            // store updated cookie values
            $this->cookies = $token->getCookies();

            // todo - catch no refresh token (and bounce to login)
        } catch (MissingTokenException $e) {
            throw new AccessDeniedHttpException('No valid credentials provided. Token may have expired');
        } catch (InvalidTokenException | InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }

        return $token->getUserId();
    }

    protected function userResponse(JsonResponse $response): JsonResponse
    {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');

        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
        return $response;
    }
}
