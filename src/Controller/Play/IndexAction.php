<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\SessionState;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\EventsService;
use App\Service\PlayerRanksService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;
    protected $usersService;

    private $applicationConfig;
    private $shipsService;
    private $playerRanksService;
    private $eventsService;

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService,
        ShipsService $shipsService,
        EventsService $eventsService
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
        $this->shipsService = $shipsService;
        $this->eventsService = $eventsService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            throw new \RuntimeException('Unable to make a new user. This went very wrong');
        }

        $state = new SessionState(
            $user,
            $this->playerRanksService->getForUser($user)
        );

        $allShips = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1000);
        $activeShips = \array_filter($allShips, function (Ship $ship) {
            return !$ship->isDestroyed();
        });
        $destroyedShips = \array_filter($allShips, function (Ship $ship) {
            return $ship->isDestroyed();
        });

        $data = [
            'sessionState' => $state,
            'activeShips' => $activeShips,
            'destroyedShips' => $destroyedShips,
            'events' => $this->eventsService->findLatestForUser($user),
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }

    protected function getUserFromRequest(Request $request): User
    {
        $user = $this->getUserIfExists($request, $this->authenticationService);
        if (!$user) {
            $user = $this->getAnonymousUser(
                $request,
                $this->usersService,
                $this->authenticationService,
                $this->applicationConfig
            );
        }
        return $user;
    }
}
