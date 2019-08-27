<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Domain\Entity\User;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Message\Error;
use App\Domain\ValueObject\Message\Messages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Route;

class LoginAnonymousAction extends AbstractLoginAction
{
    public const TOKEN_TYPE = 'loginAnon';

    public static function getRouteDefinition(): Route
    {
        return new Route(
            '/login/anonymous',
            ['_controller' => self::class,],
            [],
            [],
            '',
            [],
            ['POST']
        );
    }

    public function __invoke(
        Request $request
    ): Response {
        $loginToken = $request->get('token', '');
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
            $user = $this->getAnonymousUser($request);
        }
        return $this->getLoginResponseForUser($user);
    }

    protected function getAnonymousUser(
        Request $request
    ): User {
        if (!$this->usersService->allowedToMakeAnonymousUser($request->getClientIp())) {
            throw new TooManyRequestsHttpException(
                $this->applicationConfig->getIpLifetimeSeconds(),
                'The number of new anonymous accounts per IP address is limited. ' .
                'Please try again later or log in with another method'
            ); // todo - ensure this renders as plain text in prod
        }
        return $this->usersService->getNewAnonymousUser($request->getClientIp());
    }
}
