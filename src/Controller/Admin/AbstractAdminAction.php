<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractAdminAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    private AuthenticationService $authenticationService;

    public function __construct(
        AuthenticationService $authenticationService
    ) {
        $this->authenticationService = $authenticationService;
    }

    abstract public function invoke(Request $request): Response;

    // health status of the application itself
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser($request, $this->authenticationService);

        if (!$user->isAdmin()) {
            // Don't tell the world this page exists. Oh wait; it's in a public git repo!
            throw new NotFoundHttpException('Not Found');
        }

        return $this->noCacheResponse($this->invoke($request));
    }
}
