<?php
declare(strict_types=1);

namespace App\Controller\Players;

use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class TopPlayersAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/players', [
            '_controller' => self::class,
        ]);
    }

    public function __invoke(
        Request $request,
        UsersService $usersService,
        LoggerInterface $logger
    ): JsonResponse {

        $r = new JsonResponse([
            'players' => $usersService->getTopUsers(),
            'winners' => $usersService->getWinners(),
        ]);
        $r->setMaxAge(60 * 10);
        $r->setPublic();
        return $r;
    }
}
