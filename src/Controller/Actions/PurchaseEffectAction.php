<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Response\ShipInLocationResponse;
use App\Response\UpgradesResponse;
use App\Service\ShipsService;
use App\Service\UpgradesService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class PurchaseEffectAction extends AbstractAction
{
    private $upgradesService;
    private $usersService;
    private $shipInLocationResponse;
    private $shipsService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(PurchaseEffectToken::class);
    }

    public function __construct(
        UpgradesService $upgradesService,
        ShipsService $shipsService,
        UsersService $usersService,
        ShipInLocationResponse $shipInLocationResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
        $this->shipInLocationResponse = $shipInLocationResponse;
        $this->shipsService = $shipsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseEffectToken = $this->upgradesService->parsePurchaseEffectToken($tokenString);
        $this->upgradesService->usePurchaseEffectToken($purchaseEffectToken);

        $user = $this->usersService->getById($purchaseEffectToken->getOwnerId());
        $ship = $this->shipsService->getByIDWithLocation($purchaseEffectToken->getShipId());
        if (!$user || !$ship) {
            throw new \RuntimeException('Something went very wrong.');
        }

        return [
            'data' => $this->shipInLocationResponse->getResponseData($user, $ship),
        ];
    }
}
