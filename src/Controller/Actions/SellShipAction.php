<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\ShipsService;

class SellShipAction
{
    private ShipsService $shipsService;

    public function __construct(
        ShipsService $shipsService
    ) {
        $this->shipsService = $shipsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $sellShipToken = $this->shipsService->parseSellShipToken($tokenString);
        $this->shipsService->useSellShipToken($sellShipToken);
        return [];
    }
}
