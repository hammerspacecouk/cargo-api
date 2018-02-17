<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\Domain\Entity\UserAuthentication;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait UserAuthenticationTrait
{
    /** @var UserAuthentication */
    private $userAuthentication;

    private function getUser(
        Request $request,
        AuthenticationService $authenticationService
    ): User {
        return $this->getAuthentication($request, $authenticationService)->getUser();
    }

    private function getAuthentication(
        Request $request,
        AuthenticationService $authenticationService
    ): UserAuthentication {
        try {
            $this->userAuthentication = $authenticationService->getAuthenticationFromRequest($request);
            if (!$this->userAuthentication) {
                throw new AccessDeniedHttpException('Invalid user');
            }
            return $this->userAuthentication;
        } catch (InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }
    }

    private function clearAuthentication(
        Request $request,
        AuthenticationService $authenticationService,
        LoggerInterface $logger
    ) {
        try {
            $userAuthentication = $authenticationService->getAuthenticationFromRequest($request);
            if ($userAuthentication) {
                // we confirmed this is valid (and is ours). now remove it.
                $authenticationService->remove($userAuthentication);
            }
            $this->userAuthentication = null; // ensure we don't have a stored user any more
        } catch (\Exception $e) {
            // silently catch any errors to continue clearing the session
            $logger->error($e->getMessage());
        }
    }

    private function userResponse(
        Response $response,
        AuthenticationService $authenticationService
    ): Response {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');


        // action is over, let's ensure we handle response cookies correctly
        if ($this->userAuthentication) {
            $cookie = $authenticationService->getUpdatedCookieForResponse(
                $this->userAuthentication,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
        } else {
            $cookie = $authenticationService->makeRemovalCookie();
        }

        if ($cookie) {
            $response->headers->setCookie($cookie);
        }
        return $response;
    }
}
