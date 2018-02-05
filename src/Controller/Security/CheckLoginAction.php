<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\AuthenticationService;
use App\Service\ShipsService;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    private $authenticationService;
    private $tokensService;
    private $shipsService;
    private $usersService;
    private $logger;

    public function __construct(
        AuthenticationService $authenticationService,
        TokensService $tokensService,
        ShipsService $shipsService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->tokensService = $tokensService;
        $this->shipsService = $shipsService;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {

        $player = null;
        $loginToken = null;
        $loggedIn = false;
        $ships = null;

        try {
            $user = $this->getUser($request);
            $ships = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 100); // todo - remove hardcoding

            $loggedIn = true;
            $player = [
                'id' => $user->getId(),
                'score' => $user->getScore(),
            ];
        } catch (AccessDeniedHttpException $exception) {
            // On this controller alone, don't throw a 403. Catch and return the token needed to login
            $loginToken = (string) $this->tokensService->makeCsrfToken(LoginEmailAction::CRSF_CONTEXT_KEY);
        }

        return $this->userResponse(new JsonResponse([
            'loggedIn' => $loggedIn,
            'loginToken' => $loginToken,
            'player' => $player,
            'ships' => $ships,
        ]));
    }
}
