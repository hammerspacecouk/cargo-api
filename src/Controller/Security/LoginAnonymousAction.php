<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Message\Error;
use App\Domain\ValueObject\Message\Messages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class LoginAnonymousAction extends AbstractLoginAction
{
    public const TOKEN_TYPE = 'loginAnon';

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
        $loginToken = $request->get('loginToken', '');
        try {
            $this->usersService->verifyLoginToken($loginToken, self::TOKEN_TYPE);
        } catch (TokenException $exception) {
            $query = '?messages=' . new Messages([new Error($exception->getMessage())]);
            return new RedirectResponse(
                $this->applicationConfig->getWebHostname() . '/login' . $query
            );
        }

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
