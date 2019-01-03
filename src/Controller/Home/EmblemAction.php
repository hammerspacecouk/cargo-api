<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Controller\IDRequestTrait;
use App\Domain\ValueObject\Colour;
use App\Service\PlayerRanksService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class EmblemAction
{
    use IDRequestTrait;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/emblem/{uuid}-{colour}.svg', [
                '_controller' => self::class,
            ], [
                'uuid' => Uuid::VALID_PATTERN,
                'colour' => '^[0-9a-f]{6}$',
            ]),
        ];
    }

    public function __invoke(
        Request $request,
        PlayerRanksService $playerRanksService
    ) {
        $rankId = $this->getIDFromUrl($request);
        $colour = new Colour($request->get('colour'));

        $rank = $playerRanksService->getById($rankId);
        if (!$rank) {
            throw new NotFoundHttpException('No such emblem');
        }

        return new Response(
            $rank->getEmblem($colour),
            Response::HTTP_OK,
            [
                'content-type' => 'image/svg+xml',
                'cache-control' => 'public, max-age=' . (60 * 60 * 24 * 400),
            ],
        );
    }
}
