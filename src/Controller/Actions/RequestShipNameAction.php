<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class RequestShipNameAction extends AbstractAction
{
    private $shipsService;
    private $shipNameService;
    private $usersService;

    public function __construct(
        ShipsService $shipsService,
        ShipNameService $shipNameService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipNameService = $shipNameService;
        $this->shipsService = $shipsService;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [REQUEST SHIP NAME]');

        $token = $this->shipNameService->parseRequestShipNameToken($tokenString);
        $shipName = $this->shipNameService->useRequestShipNameToken($token);

        $actionToken = $this->shipNameService->getRenameShipToken(
            $token->getShipId(),
            $shipName
        );

        // the previous token should not be reusable, so we need to send a new one
        $requestShipNameTransaction = $this->shipNameService->getRequestShipNameTransaction(
            $token->getUserId(),
            $token->getShipId()
        );

        $user = $this->usersService->getById($token->getUserId());

        return [
            'nameOffered' => $shipName,
            'action' => $actionToken,
            'requestShipName' => $requestShipNameTransaction,
            'newScore' => $user->getScore(),
        ];
    }
}
