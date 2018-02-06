<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Service\PortsService;
use App\Service\ShipsService;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $shipsService;
    private $portsService;
    private $usersService;
    private $logger;

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        PortsService $portsService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {

        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);
        $userId = $user->getId();

        $homePort = $this->portsService->findHomePortForUserId($userId);

        $statusKey = 'ACTIVE';

        // if you have no ships, send you to the welcome action
        if (!$homePort) {
            $statusKey = 'WELCOME';
            $this->usersService->startPlayer($userId);
            $this->logger->notice('[NEW PLAYER] [' . (string)$userId . ']');

            // homePort definitely exists now
            $homePort = $this->portsService->findHomePortForUserId($userId);
        }

        $ships = $this->shipsService->getForOwnerIDWithLocation($userId, 100);

        $status = [
            'status' => $statusKey,
            'userId' => $userId,
            'ships' => $ships,
            'homePort' => $homePort,
        ];

        return $this->userResponse(new JsonResponse($status), $this->authenticationService);
    }
}
