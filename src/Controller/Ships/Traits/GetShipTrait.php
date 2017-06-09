<?php
declare(strict_types = 1);
namespace App\Controller\Ships\Traits;

use App\Controller\IDRequestTrait;
use App\Domain\Entity\Ship;
use App\Service\ShipsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait GetShipTrait
{
    use IDRequestTrait;

    public function getShip(
        Request $request,
        ShipsService $shipsService
    ): Ship {
        $uuid = $this->getID($request);
        $ship = $shipsService->getByID($uuid);
        if (!$ship) {
            throw new NotFoundHttpException('No such ship');
        }
        return $ship;
    }

    public function getShipWithLocation(
        Request $request,
        ShipsService $shipsService
    ): Ship {
        $uuid = $this->getID($request);
        $ship = $shipsService->getByID($uuid);
        if (!$ship) {
            throw new NotFoundHttpException('No such ship');
        }
        return $ship;
    }
}
