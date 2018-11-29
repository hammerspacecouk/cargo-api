<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class LoginAction
{
    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/login', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        return new JsonResponse([
            'email' => '/login/email',
            'facebook' => '/login/facebook',
            'google' => '/login/google',
            'microsoft' => '/login/microsoft',
            'twitter' => '/login/twitter',
        ]);
    }
}
