<?php
declare(strict_types=1);

namespace App\Controller\Ships;

use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ShowAction
{
    use Traits\GetShipTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/ships/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => (new GenericValidator())->getPattern(),
        ]);
    }

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
