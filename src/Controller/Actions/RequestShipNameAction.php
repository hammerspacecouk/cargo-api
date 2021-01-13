<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\Ships\ShipNameService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class RequestShipNameAction
{
    public function __construct(
        private ShipNameService $shipNameService,
        private UsersService $usersService,
        private LoggerInterface $logger
    ) {
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [REQUEST SHIP NAME]');

        $token = $this->shipNameService->parseRequestShipNameToken($tokenString);
        $shipName = $this->shipNameService->useRequestShipNameToken($token);

        $actionToken = $this->shipNameService->getRenameShipToken(
            $token->getUserId(),
            $token->getShipId(),
            $shipName,
        );

        // the previous token should not be reusable, so we need to send a new one
        $requestShipNameTransaction = $this->shipNameService->getRequestShipNameTransaction(
            $token->getUserId(),
            $token->getShipId(),
        );

        $user = $this->usersService->getById($token->getUserId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'nameOffered' => $shipName,
            'action' => $actionToken,
            'shipId' => $token->getShipId(),
            'newRequestShipNameToken' => $requestShipNameTransaction,
            'newScore' => $user->getScore(),
        ];
    }
}
