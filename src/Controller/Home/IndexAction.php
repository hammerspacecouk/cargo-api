<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Service\CratesService;
use App\Service\EventsService;
use App\Service\UsersService;
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
        CratesService $cratesService,
        EventsService $eventsService,
        UsersService $usersService
    ): JsonResponse {

        // todo - public cache headers
        return new JsonResponse([
            'events' => $eventsService->findAllLatest(15),
            'goalCrateLocation' => $cratesService->findGoalCrateLocation(),
            'topPlayer' => $usersService->getTopUsers()[0] ?? null,
            'topWinner' => $usersService->getWinners()[0] ?? null,
        ]);
    }
}
