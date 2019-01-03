<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Controller\IDRequestTrait;
use App\Service\Ships\ShipClassService;
use App\Service\ShipsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipClassImageAction
{
    use IDRequestTrait;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/ship-class/{uuid}-{hash}.svg', [
                '_controller' => self::class,
            ], [
                'uuid' => Uuid::VALID_PATTERN,
                'hash' => '^[0-9a-f]{40}$',
            ]),
        ];
    }

    public function __invoke(
        Request $request,
        ShipClassService $shipClassService
    ) {
        $classId = $this->getIDFromUrl($request);
        $hash = $request->get('hash', '');

        $class = $shipClassService->fetchById($classId);
        if (!$class || $hash !== $class->getImageHash()) {
            throw new NotFoundHttpException('No such image');
        }

        return new Response(
            $class->getImage(),
            Response::HTTP_OK,
            [
                'content-type' => 'image/svg+xml',
                'cache-control' => 'public, max-age=' . (60 * 60 * 24 * 400),
            ],
        );
    }
}
