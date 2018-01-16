<?php
declare(strict_types=1);

namespace App\Controller\Security\Traits;

use App\Domain\Entity\User;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\MissingTokenException;
use App\Service\TokensService;
use App\Service\UsersService;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait UserTokenTrait
{
    private $cookies = [];

    private function getUserId(
        Request $request,
        TokensService $tokensService
    ): UuidInterface {

        try {
            // try and get back a userId
            $token = $tokensService->getAccessTokenFromRequest($request);

            // store updated cookie values
            $this->cookies = $token->getCookies();
        } catch (MissingTokenException $e) {
            throw new AccessDeniedHttpException('No valid credentials provided. Token may have expired');
        } catch (InvalidTokenException | InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }

        return $token->getUserId();
    }

    private function getUser(
        Request $request
    ): User {
        $userId = $this->getUserId($request, $this->tokensService);
        $user = $this->usersService->getById($userId);
        if ($user) {
            return $user;
        }
        throw new AccessDeniedHttpException('Invalid user');
    }

    private function userResponse(Response $response): Response
    {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');

        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
        return $response;
    }
}
