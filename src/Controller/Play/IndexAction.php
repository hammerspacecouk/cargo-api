<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\Security\CheckLoginAction;
use App\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

// This class does the same thing as CheckLogin, but will create a new anonymous user if you're not logged in
class IndexAction extends CheckLoginAction
{
    protected function getUserFromRequest(Request $request): ?User
    {
        try {
            return $this->getUser($request, $this->authenticationService);
        } catch (AccessDeniedHttpException $exception) {
            // On this controller, don't throw a 403, make a new anonymous user
            return $this->getAnonymousUser($request, $this->usersService, $this->authenticationService);
        }
    }
}
