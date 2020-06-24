<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use App\Service\PortsService;
use App\Service\PurchasesService;
use App\Service\UsersService;

class ProfileResponse
{
    private AuthenticationService $authenticationService;
    private PortsService $portsService;
    private UsersService $usersService;
    private PurchasesService $purchasesService;

    public function __construct(
        AuthenticationService $authenticationService,
        PortsService $portsService,
        PurchasesService $purchasesService,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
        $this->purchasesService = $purchasesService;
    }

    public function getResponseDataForUser(User $user): array
    {
        $homePort = $this->portsService->findHomePortForUserId($user->getId());

        return [
            'isAnonymous' => $user->isAnonymous(),
            'isTrial' => $user->isTrial(),
            'status' => $user->getStatus(),
            'canDelete' => $this->usersService->canUserDelete($user),
            'homePort' => $homePort,
            'purchases' => $this->purchasesService->getAllForUser($user),
            'distanceTravelled' => $user->getLightYearsTravelled(),
            'authProviders' => $this->authenticationService->getAuthProviders($user),
        ];
    }
}
