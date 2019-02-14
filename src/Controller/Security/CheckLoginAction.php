<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\ValueObject\LoginOptions;
use App\Domain\ValueObject\SessionState;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class CheckLoginAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;
    protected $usersService;

    private $playerRanksService;
    private $applicationConfig;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/login/check', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
        $this->applicationConfig = $applicationConfig;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUserIfExists($request, $this->authenticationService);
        if ($user) {
            $state = new SessionState(
                $user,
                $this->playerRanksService->getForUser($user)
            );
        } else {
            $loginOptions = new LoginOptions(
                $this->applicationConfig->isLoginAnonEnabled() ?
                    $this->usersService->getLoginToken(LoginAnonymousAction::TOKEN_TYPE) : null,
                $this->applicationConfig->isLoginEmailEnabled() ?
                    $this->usersService->getLoginToken(LoginEmailAction::TOKEN_TYPE) : null,
                $this->applicationConfig->isLoginFacebookEnabled(),
                $this->applicationConfig->isLoginGoogleEnabled(),
                $this->applicationConfig->isLoginMicrosoftEnabled(),
                $this->applicationConfig->isLoginTwitterEnabled(),
            );

            $state = new SessionState(
                null,
                null,
                $loginOptions,
            );
        }

        return $this->userResponse(new JsonResponse($state), $this->authenticationService);
    }
}
