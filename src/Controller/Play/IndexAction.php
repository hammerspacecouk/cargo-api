<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Domain\ValueObject\SessionState;
use App\Response\FleetResponse;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
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
    private $shipNameService;
    private $fleetResponse;

    public function __construct(
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService,
        FleetResponse $fleetResponse
    ) {
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
        $this->fleetResponse = $fleetResponse;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUser($request, $this->authenticationService);

        $state = new SessionState(
            $user,
            $this->playerRanksService->getForUser($user)
        );

        $data = [
            'sessionState' => $state,
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }
}
