<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\UserAuthenticationTrait;
use App\Service\AuthenticationService;
use App\Service\PortsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class ShowAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $portsService;
    private $usersService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PortsService $portsService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUser($request, $this->authenticationService);

        $homePort = $this->portsService->findHomePortForUserId($user->getId());

        return $this->userResponse(new JsonResponse([
            'isAnonymous' => $user->isAnonymous(),
            'isTrial' => true, // todo - real value
            'canDelete' => $this->usersService->canUserDelete($user),
            'homePort' => $homePort,
            'authProviders' => $this->authenticationService->getAuthProviders($user),
        ]), $this->authenticationService);
    }
}
