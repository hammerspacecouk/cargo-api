<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\Domain\Entity\UserAuthentication;
use App\Domain\Exception\NoUserHttpException;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait UserAuthenticationTrait
{
    protected function getUser(
        Request $request,
        AuthenticationService $authenticationService
    ): User {
        return $this->getAuthentication($request, $authenticationService)->getUser();
    }

    protected function getUserIfExists(
        Request $request,
        AuthenticationService $authenticationService
    ): ?User {
        try {
            return $this->getAuthentication($request, $authenticationService)->getUser();
        } catch (NoUserHttpException $exception) {
            return null;
        }
    }

    protected function getAuthentication(
        Request $request,
        AuthenticationService $authenticationService
    ): UserAuthentication {
        try {
            $userAuthentication = $authenticationService->getAuthenticationFromRequest($request);
            if (!$userAuthentication) {
                throw new NoUserHttpException('Invalid user');
            }
            return $userAuthentication;
        } catch (InvalidUuidStringException $e) {
            throw new NoUserHttpException('Invalid user - token invalid: ' . $e->getMessage());
        }
    }

    protected function logoutResponse(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService
    ): Response {
        $response = new RedirectResponse(
            $applicationConfig->getWebHostname() . '#logout'
        );
        $response->headers->setCookie($authenticationService->makeRemovalCookie());
        return $response;
    }
}
