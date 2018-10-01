<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        $data = [
            'sessionState' => $state,
            'ships' => $this->shipsService->getForOwnerIDWithLocation($user->getId(), 100), // todo - paginate
            'events' => $this->eventsService->findLatestForUser($user),
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }

    protected function getUserFromRequest(Request $request): User
    {
        try {
            return $this->getUser($request, $this->authenticationService);
        } catch (AccessDeniedHttpException $exception) {
            // On this controller, don't throw a 403, make a new anonymous user
            return $this->getAnonymousUser(
                $request,
                $this->usersService,
                $this->authenticationService,
                $this->applicationConfig
            );
        }
    }
}
