<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\UserAuthenticationTrait;
use App\Domain\ValueObject\SessionState;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\PortsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class ShowAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $portsService;
    private $usersService;
    private $playerRanksService;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/profile', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        PortsService $portsService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $authentication = $this->getAuthentication($request, $this->authenticationService);
        $player = $authentication->getUser();

        $homePort = $this->portsService->findHomePortForUserId($player->getId());

        return $this->userResponse(new JsonResponse([
            'isAnonymous' => !$player->hasEmailAddress(),
            'canDelete' => $this->usersService->canUserDelete($player),
            'homePort' => $homePort,
            'session' => new SessionState(
                $player,
                $this->playerRanksService->getForUser($player)
            ),
        ]), $this->authenticationService);
    }
}
