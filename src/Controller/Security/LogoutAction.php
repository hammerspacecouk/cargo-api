<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class LogoutAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    private $authenticationService;
    private $applicationConfig;

    public static function getRouteDefinition(): Route
    {
        return new Route('/logout', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
    }

    public function __invoke(
        Request $request
    ): Response {
        $userAuthentication = $this->authenticationService->getAuthenticationFromRequest($request, false);
        if ($userAuthentication) {
            $this->authenticationService->remove($userAuthentication);
        }
        $logoutResponse = $this->logoutResponse($this->applicationConfig, $this->authenticationService);
        return $this->noCacheResponse($logoutResponse);
    }
}
