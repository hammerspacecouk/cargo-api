<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AbstractUserAction;
use App\Service\AuthenticationService;
use App\Service\PortsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ShowAction extends AbstractUserAction
{
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
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->portsService = $portsService;
        $this->usersService = $usersService;
    }

    public function invoke(
        Request $request
    ): array {
        $homePort = $this->portsService->findHomePortForUserId($this->user->getId());

        return [
            'isAnonymous' => $this->user->isAnonymous(),
            'isTrial' => true, // todo - real value
            'canDelete' => $this->usersService->canUserDelete($this->user),
            'homePort' => $homePort,
            'authProviders' => $this->authenticationService->getAuthProviders($this->user),
        ];
    }
}
