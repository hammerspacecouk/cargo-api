<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\ValueObject\SessionState;
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

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/login/check', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
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
            // todo - make a CSRF token for Anonymous login
            $state = new SessionState();
        }

        return $this->userResponse(new JsonResponse($state), $this->authenticationService);
    }
}
