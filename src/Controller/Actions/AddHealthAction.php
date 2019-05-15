<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Response\ShipInLocationResponse;
use App\Service\Ships\ShipHealthService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddHealthAction extends AbstractAction
{
    private $shipHealthService;
    private $usersService;
    private $shipInLocationResponse;
    private $shipsService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(ShipHealthToken::class);
    }

    public function __construct(
        ShipHealthService $shipHealthService,
        UsersService $usersService,
        ShipsService $shipsService,
        ShipInLocationResponse $shipInLocationResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->shipHealthService = $shipHealthService;
        $this->usersService = $usersService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->shipsService = $shipsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $this->logger->notice('[ACTION] [SHIP HEALTH]');

        $token = $this->shipHealthService->parseShipHealthToken($tokenString);
        $this->shipHealthService->useShipHealthToken($token);

        $user = $this->usersService->getById($token->getUserId());
        $ship = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$user || !$ship) {
            throw new \RuntimeException('Something went very wrong here');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $ship),
        ];
    }
}
