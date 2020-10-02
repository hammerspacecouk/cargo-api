<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Controller\IDRequestTrait;
use App\Service\UsersService;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class EmblemAction
{
    use IDRequestTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/emblem/{uuid}-{hash}.svg', [
            '_controller' => self::class,
        ], [
            'uuid' => '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$',
            // TODO - Symfony 5.2 // (new GenericValidator())->getPattern(),
            'hash' => '^[0-9a-f]{32}$',
        ]);
    }

    public function __invoke(
        Request $request,
        UsersService $userService
    ): Response {
        $user = $userService->getById($this->getIDFromUrl($request));
        if (!$user) {
            throw new NotFoundHttpException('No such emblem');
        }

        return new Response(
            $user->getEmblem(),
            Response::HTTP_OK,
            [
                'content-type' => 'image/svg+xml',
                'cache-control' => 'public, immutable, max-age=' . (60 * 60 * 24 * 400),
            ],
        );
    }
}
