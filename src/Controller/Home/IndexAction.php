<?php declare(strict_types=1);

namespace App\Controller\Home;

use App\Domain\Entity\ShipInChannel;
use App\Domain\Entity\ShipInPort;
use App\Service\ShipLocationsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        ShipLocationsService $shipsLocationsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);

        $latestShipLocations = $shipsLocationsService->findLatest(10);

        $shipsStatuses = [];
        foreach ($latestShipLocations as $location) {
            if ($location instanceof ShipInPort) {
                $shipsStatuses[] = $location->getShip()->getName() . ' arrived at ' . $location->getPort()->getName();
            } elseif ($location instanceof ShipInChannel) {
                $shipsStatuses[] = $location->getShip()->getName() .
                    ' departed ' . $location->getOrigin()->getName() .
                    ' headed for ' . $location->getDestination()->getName();
            }
        }

        return new JsonResponse([
            'status' => 'ok',
            'updates' => $shipsStatuses,
        ]);
    }
}
