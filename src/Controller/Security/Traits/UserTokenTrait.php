<?php
declare(strict_types=1);

namespace App\Controller\Security\Traits;

use App\Domain\Entity\User;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait UserTokenTrait
{
    private $cookies = [];

    private function getUser(
        Request $request
    ): User {
        try {
            $user = $this->authenticationService->getUserFromRequest($request);
            if ($user) {
                return $user;
            }
            throw new AccessDeniedHttpException('Invalid user');
        } catch (InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }
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
