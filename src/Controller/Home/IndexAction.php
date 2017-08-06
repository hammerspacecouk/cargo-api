<?php
declare(strict_types = 1);
namespace App\Controller\Home;

use App\Domain\Entity\Ship;
use App\Service\ShipsService;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        ShipsService $shipsService
    ): JsonResponse {

        $latestShipLocations = $shipsService->findLatestShipLocations(10);

        $shipsStatuses = [];
        foreach ($latestShipLocations as $ship) {
            /** @var Ship $ship */
            $shipsStatuses[] = $ship->getName() . ' arrived at ' . $ship->getLocation()->getName();
        }


        return new JsonResponse([
            'status' => 'ok',
            'updates' => $shipsStatuses,
        ]);
    }
}
