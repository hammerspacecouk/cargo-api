<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Service\Ships\ShipHealthService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class AddHealthAction extends AbstractAction
{
    private $shipHealthService;
    private $usersService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(ShipHealthToken::class);
    }

    public function __construct(
        ShipHealthService $shipHealthService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipHealthService = $shipHealthService;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [SHIP HEALTH]');

        $token = $this->shipHealthService->parseShipHealthToken($tokenString);
        $newHealth = $this->shipHealthService->useShipHealthToken($token);

        // the previous tokens are now unusable, so todo - make new ones


        $user = $this->usersService->getById($token->getUserId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            // todo - new tokens
            'newScore' => $user->getScore(),
            'newHealth' => $newHealth,
        ];
    }
}
