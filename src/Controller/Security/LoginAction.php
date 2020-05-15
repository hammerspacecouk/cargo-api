<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Domain\ValueObject\LoginOptions;
use App\Infrastructure\ApplicationConfig;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class LoginAction
{
    private ApplicationConfig $applicationConfig;
    private UsersService $usersService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/login', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        ApplicationConfig $applicationConfig,
        UsersService $usersService
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        $response = new JsonResponse(new LoginOptions(
            $this->applicationConfig->isLoginAnonEnabled() ?
                $this->usersService->getLoginToken(LoginAnonymousAction::TOKEN_TYPE) : null,
            $this->applicationConfig->isLoginFacebookEnabled(),
            $this->applicationConfig->isLoginGoogleEnabled(),
            $this->applicationConfig->isLoginMicrosoftEnabled(),
            $this->applicationConfig->isLoginTwitterEnabled(),
            $this->applicationConfig->isLoginRedditEnabled(),
        ));

        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
        return $response;
    }
}
