<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckLoginAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $shipsService;
    private $usersService;
    private $logger;
    private $playerRanksService;

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->usersService = $usersService;
        $this->logger = $logger;
        $this->playerRanksService = $playerRanksService;
    }

    public function __invoke(
        Request $request
    ): Response {

        $player = null;
        $loggedIn = false;
        $ships = null;

        try {
            $user = $this->getUser($request, $this->authenticationService);
        } catch (AccessDeniedHttpException $exception) {
            // On this controller alone, don't throw a 403, create a brand new user
            $user = $this->getAnonymousUser($request, $this->usersService, $this->authenticationService);
        }

        if ($user) {
            $loggedIn = true;
            $ships = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 100); // todo - remove hardcoding
            $player = [
                'id' => $user->getId(),
                'score' => $user->getScore(),
                'colour' => $user->getColour(),
                'rankStatus' => $this->playerRanksService->getForUser($user),
            ];
        }

        return $this->userResponse(new JsonResponse([
            'loggedIn' => $loggedIn,
            'player' => $player,
            'ships' => $ships,
        ]), $this->authenticationService);
    }
}
