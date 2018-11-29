<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\UserAuthentication;
use App\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class SessionsAction
{
    use UserAuthenticationTrait;

    private $authenticationService;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/profile/sessions', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService
    ) {
        $this->authenticationService = $authenticationService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $authentication = $this->getAuthentication($request, $this->authenticationService);
        $tokens = $this->authenticationService->findAllForUser($authentication->getUser());

        $sessions = array_map(function (UserAuthentication $token) use ($authentication) {
            $isCurrent = $token->getId()->equals($authentication->getId());

            return [
                'isCurrent' => $isCurrent,
                'removeToken' => $isCurrent? null : 'todo', // todo - make an action token based on the ID
                'state' => $token,
            ];
        }, $tokens);

        return $this->userResponse(new JsonResponse([
            'sessions' => $sessions,
        ]), $this->authenticationService);
    }
}
