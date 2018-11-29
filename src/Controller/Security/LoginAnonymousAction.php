<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class LoginAnonymousAction extends AbstractLoginAction
{
    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route(
                '/login/anonymous',
                ['_controller' => self::class,],
                [],
                [],
                '',
                [],
                ['POST']
            ),
        ];
    }

    public function __invoke(
        Request $request
    ): RedirectResponse {
        // todo - validate Token for CSRF

        // do you already have a session
        $user = $this->getUserIfExists($request, $this->authenticationService);
        if (!$user) {
            // todo - catch TooManyRequestsHttpException and redirect to explanation page
            $user = $this->getAnonymousUser(
                $request,
                $this->usersService,
                $this->authenticationService,
                $this->applicationConfig
            );
        }
        return $this->getLoginResponseForUser($user);
    }
}
