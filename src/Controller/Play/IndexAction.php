<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\Security\LoginAnonymousAction;
use App\Controller\Security\LoginEmailAction;
use App\Controller\UserAuthenticationTrait;
use App\Domain\ValueObject\LoginOptions;
use App\Domain\ValueObject\SessionState;
use App\Infrastructure\ApplicationConfig;
use App\Response\FleetResponse;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class IndexAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;
    protected $usersService;

    private $applicationConfig;
    private $shipsService;
    private $playerRanksService;
    private $eventsService;
    private $shipNameService;
    private $fleetResponse;

    public static function getRouteDefinition(): array
    {
        return [
            static::class => new Route('/play', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService,
        FleetResponse $fleetResponse
    ) {
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
        $this->fleetResponse = $fleetResponse;
        $this->applicationConfig = $applicationConfig;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUserIfExists($request, $this->authenticationService);
        $fleet = null;

        if ($user) {
            $sessionState = new SessionState(
                $user,
                $this->playerRanksService->getForUser($user)
            );
            $fleet = $this->fleetResponse->getResponseDataForUser($user);
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

            $sessionState = new SessionState(
                null,
                null,
                $loginOptions,
                );
        }

        $data = [
            'sessionState' => $sessionState,
            'fleet' => $fleet,
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }
}
