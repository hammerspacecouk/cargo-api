<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\Routing\Route;

class LoginGoogleAction extends AbstractOauthLoginAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/login/google', [
            '_controller' => self::class,
        ]);
    }
}
