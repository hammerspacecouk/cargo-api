<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckLoginAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;
    protected $usersService;

    private $shipsService;
    private $logger;
    private $playerRanksService;

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        PlayerRanksService $playerRanksService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUserFromRequest($request);
        $data = [
            'loggedIn' => false,
            'player' => null,
            'ships' => [],
            'hasSetEmail' => false,
            'rankStatus' => null,
        ];

        if ($user) {
            $data['loggedIn'] = true;
            $data['player'] = $user;
            $data['ships'] = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 100); // todo - remove hardcoding (max ships per user)
            $data['hasSetEmail'] = $user->hasEmailAddress();
            $data['rankStatus'] = $this->playerRanksService->getForUser($user);
        }

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }

    protected function getUserFromRequest(Request $request): ?User
    {
        try {
            return $this->getUser($request, $this->authenticationService);
        } catch (AccessDeniedHttpException $exception) {
            // On this controller alone, don't throw a 403, return empty data
            return null;
        }
    }
}
