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
use Symfony\Component\Routing\Route;

class IndexAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;
    protected $usersService;

    private $shipsService;
    private $playerRanksService;
    private $eventsService;
    private $shipNameService;
    private $fleetResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play', [
            '_controller' => self::class,
        ]);
    }

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
        $fleet = null;

        $data = [
            'sessionState' => new SessionState(
                $user,
                $this->playerRanksService->getForUser($user)
            ),
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }
}
