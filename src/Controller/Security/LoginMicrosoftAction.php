<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\Routing\Route;

class LoginMicrosoftAction extends AbstractOauthLoginAction
{
    public static function getRouteDefinition(): Route
    {
        return new Route('/login/microsoft', [
            '_controller' => self::class,
        ]);
    }
}
