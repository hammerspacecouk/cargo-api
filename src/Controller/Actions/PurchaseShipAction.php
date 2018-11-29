<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\PurchaseShipToken;
use App\Service\UpgradesService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class PurchaseShipAction extends AbstractAction
{
    private $upgradesService;
    private $usersService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(PurchaseShipToken::class);
    }

    public function __construct(
        UpgradesService $upgradesService,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseShipToken = $this->upgradesService->parsePurchaseShipToken($tokenString);
        $message = $this->upgradesService->usePurchaseShipToken($purchaseShipToken);

        $user = $this->usersService->getById($purchaseShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        // send back the new state of the upgrades. todo - abstract to re-use
        $data = [
            'message' => $message,
            'newScore' => $user->getScore(),
            'ships' => $this->upgradesService->getAvailableShipsForUser($user),
            'repairs' => [],
            'weapons' => [],
            'navigation' => [],
        ];
        return $data;
    }
}
