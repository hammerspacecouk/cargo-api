<?php
declare(strict_types=1);

namespace App\Controller\Ships;

use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShowAction
{
    use Traits\GetShipTrait;

    public function __invoke(
        Request $request,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);
        $ship = $this->getShipWithLocation($request, $shipsService);
        return new JsonResponse($ship);
    }
}
