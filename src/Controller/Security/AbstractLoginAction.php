<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\User;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function App\Functions\Strings\startsWith;

abstract class AbstractLoginAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    protected ApplicationConfig $applicationConfig;
    protected AuthenticationService $authenticationService;
    protected UsersService $usersService;
    protected ShipsService $shipsService;

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        UsersService $usersService
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->authenticationService = $authenticationService;
        $this->usersService = $usersService;
        $this->shipsService = $shipsService;
    }

    protected function getRedirectUrl(Request $request): string
    {
        $redirectUrl = $request->get('r');
        if ($redirectUrl && (!is_string($redirectUrl) || !startsWith('/', $redirectUrl))) {
            throw new BadRequestHttpException('Bad redirect parameter');
        }

        if (!$redirectUrl || $redirectUrl === '/' || $redirectUrl === '/login') {
            // don't send logged in users back to home or login. send them straight to the action
            $redirectUrl = $this->getDefaultRedirectUrl();
        }
        return $redirectUrl;
    }

    protected function getDefaultRedirectUrl(): string
    {
        return '/play';
    }

    protected function getLoginResponseForUser(User $user, string $url = null): Response
    {
        $cookie = $this->authenticationService->makeNewAuthenticationCookie($user);

        $url = $url ?? $this->getDefaultRedirectUrl();

        // if this is a brand new user, send them to the intro page
        if ($user->getRank()->isTutorial()) {
            $url = '/play/intro';
        }

        $response = new RedirectResponse($this->applicationConfig->getWebHostname() . $url);
        $response->headers->setCookie($cookie);
        return $this->noCacheResponse($response);
    }
}
