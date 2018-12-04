<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Response\FleetResponse;
use App\Service\Ships\ShipHealthService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class AddHealthAction extends AbstractAction
{
    private $shipHealthService;
    private $usersService;
    private $fleetResponse;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(ShipHealthToken::class);
    }

    public function __construct(
        ShipHealthService $shipHealthService,
        UsersService $usersService,
        FleetResponse $fleetResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipHealthService = $shipHealthService;
        $this->usersService = $usersService;
        $this->fleetResponse = $fleetResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [SHIP HEALTH]');

        $token = $this->shipHealthService->parseShipHealthToken($tokenString);
        $this->shipHealthService->useShipHealthToken($token);

        $user = $this->usersService->getById($token->getUserId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'newScore' => $user->getScore(),
            'fleet' => $this->fleetResponse->getResponseDataForUser($user),
        ];
    }
}
