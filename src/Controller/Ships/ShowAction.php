<?php
declare(strict_types = 1);
namespace App\Controller\Ships;

use App\Service\ShipsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShowAction
{
    use Traits\GetShipTrait;

    public function __invoke(
        Request $request,
        ShipsService $shipsService
    ): JsonResponse {
        $ship = $this->getShip($request, $shipsService);
        return new JsonResponse($ship);
    }
}
