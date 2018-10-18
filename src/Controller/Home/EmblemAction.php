<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Controller\IDRequestTrait;
use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class EmblemAction
{
    use IDRequestTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/emblem/{uuid}/{hash}.svg', [
            '_controller' => self::class,
        ], [
            'uuid' => Uuid::VALID_PATTERN,
            'hash' => '^[0-9a-f]{40}$',
        ]);
    }

    public function __invoke(
        Request $request,
        UsersService $usersService,
        PlayerRanksService $playerRanksService
    ) {
        $playerId = $this->getID($request);
        $player = $usersService->getById($playerId);
        if (!$player) {
            throw new NotFoundHttpException('No such player');
        }

        $rank = $playerRanksService->getForUser($player);

        return new Response(
            $rank->getEmblem($player->getColour()),
            Response::HTTP_OK,
            [
                'content-type' => 'image/svg+xml',
                'cache-control' => 'public, max-age=' . (60 * 60 * 24 * 400),
            ]
        );
    }
}
