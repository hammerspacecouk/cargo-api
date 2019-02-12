<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Response\UpgradesResponse;
use App\Service\UpgradesService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;

class PurchaseEffectAction extends AbstractAction
{
    private $upgradesService;
    private $usersService;
    private $upgradesResponse;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(PurchaseEffectToken::class);
    }

    public function __construct(
        UpgradesService $upgradesService,
        UpgradesResponse $upgradesResponse,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->upgradesService = $upgradesService;
        $this->usersService = $usersService;
        $this->upgradesResponse = $upgradesResponse;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $purchaseEffectToken = $this->upgradesService->parsePurchaseEffectToken($tokenString);
        $this->upgradesService->usePurchaseEffectToken($purchaseEffectToken);

        $user = $this->usersService->getById($purchaseEffectToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        // send back the new state of the upgrades
        $data = [
            'newScore' => $user->getScore(),
            'upgrades' => $this->upgradesResponse->getResponseDataForUser($user),
        ];
        return $data;
    }
}
