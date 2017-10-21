<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Domain\Entity\Port;
use App\Service\PortsService;
use App\Service\ShipsService;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The 'My' Section reads from your cookie, so is custom and un-cacheable
 */
class IndexAction
{
    use UserTokenTrait;

    private $tokensService;
    private $shipsService;
    private $portsService;
    private $usersService;
    private $logger;

    public function __construct(
        TokensService $tokensService,
        ShipsService $shipsService,
        PortsService $portsService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->tokensService = $tokensService;
        $this->shipsService = $shipsService;
        $this->portsService = $portsService;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): JsonResponse {

        $this->logger->debug(__CLASS__);
        $userId = $this->getUserId($request, $this->tokensService);

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

        return $this->userResponse(new JsonResponse($status));
    }
}
