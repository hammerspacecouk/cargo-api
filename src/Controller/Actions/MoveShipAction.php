<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Response\ShipInChannelResponse;
use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class MoveShipAction extends AbstractAction
{
    private $shipMovementService;
    private $usersService;
    /**
     * @var ShipInChannelResponse
     */
    private $shipInChannelResponse;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(MoveShipToken::class);
    }

    public function __construct(
        ShipMovementService $shipMovementService,
        UsersService $usersService,
        ShipInChannelResponse $shipInChannelResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
        $this->shipInChannelResponse = $shipInChannelResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $moveShipToken = $this->shipMovementService->parseMoveShipToken($tokenString);
        $newChannelLocation = $this->shipMovementService->useMoveShipToken($moveShipToken);

        // send back the new state of the ship in the channel and the new state of the user
        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        return $this->shipInChannelResponse->getResponseData(
            $user,
            $this->shipMovementService->getByID($moveShipToken->getShipId()),
            $newChannelLocation
        );
    }
}
