<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Service\EventsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;

class IndexAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/', [
            '_controller' => self::class,
        ]);
    }

    // general status and stats of the game as a whole
    public function __invoke(
        EventsService $eventsService
    ): JsonResponse {

        // todo - public cache headers
        return new JsonResponse([
            'events' => $eventsService->findAllLatest(15),
        ]);
    }
}
