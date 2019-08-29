<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use App\Service\PortsService;
use App\Service\UsersService;

class ProfileResponse
{
    private $authenticationService;
    private $portsService;
    private $usersService;

    public function __construct(
        AuthenticationService $authenticationService,
        PortsService $portsService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
    }

    public function getResponseDataForUser(User $user): array
    {
        $homePort = $this->portsService->findHomePortForUserId($user->getId());

        return [
            'isAnonymous' => $user->isAnonymous(),
            'isTrial' => true, // todo - real value
            'canDelete' => $this->usersService->canUserDelete($user),
            'homePort' => $homePort,
            'authProviders' => $this->authenticationService->getAuthProviders($user),
        ];
    }

}
