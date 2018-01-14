<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\TokensService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        TokensService $tokensService,
        UsersService $usersService
    ): Response {

        $player = null;
        $loginToken = null;
        $loggedIn = false;

        try {
            $user = $this->getUser($request, $tokensService, $usersService);
            $loggedIn = true;
            $player = [
                'id' => $user->getId(),
                'score' => $user->getScore(),
            ];
        } catch (AccessDeniedHttpException $exception) {
            // On this controller alone, don't throw a 403. Catch and return the token needed to login
            $loginToken = (string) $tokensService->makeCsrfToken(LoginEmailAction::CRSF_CONTEXT_KEY);
        }

        return $this->userResponse(new JsonResponse([
            'loggedIn' => $loggedIn,
            'loginToken' => $loginToken,
            'player' => $player,
        ]));
    }
}
