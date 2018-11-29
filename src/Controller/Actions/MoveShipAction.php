<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class MoveShipAction extends AbstractAction
{
    private $shipMovementService;
    private $usersService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(MoveShipToken::class);
    }

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
            'shipsInLocation' => null,
            'events' => null,
        ];

        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }
        $data['playerScore'] = $user->getScore();
        return $data;
    }
}
