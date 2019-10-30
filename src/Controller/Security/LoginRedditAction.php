<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\Routing\Route;

class LoginRedditAction extends AbstractOauthLoginAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/login/reddit', [
            '_controller' => self::class,
        ]);
    }
}
