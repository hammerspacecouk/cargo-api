<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\User;
use App\Domain\ValueObject\SessionState;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
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

    private $playerRanksService;

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
        $user = $this->getUserFromRequest($request);
        if ($user) {
            $state = new SessionState(
                $user,
                $this->playerRanksService->getForUser($user)
            );
        } else {
            $state = new SessionState();
        }

        return $this->userResponse(new JsonResponse($state), $this->authenticationService);
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
