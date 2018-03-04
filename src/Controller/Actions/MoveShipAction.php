<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class MoveShipAction extends AbstractAction
{
    private $shipMovementService;
    private $usersService;

    public function __construct(
        ShipMovementService $shipMovementService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {

        $moveShipToken = $this->shipMovementService->parseMoveShipToken($tokenString);
        $newChannelLocation = $this->shipMovementService->useMoveShipToken($moveShipToken);

        // send back the new state of the ship in the channel and the new state of the user
        $data = [
            'port' => null,
            'channel' => $newChannelLocation,
            'directions' => null,
            'players' => null, // todo - get the players in the channel
        ];

        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        $data['playerScore'] = $user->getScore();
        return $data;
    }
}
