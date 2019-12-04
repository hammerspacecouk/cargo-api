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

    /**
     * @var ApplicationConfig
     */
    protected $applicationConfig;
    /**
     * @var AuthenticationService
     */
    protected $authenticationService;
    /**
     * @var UsersService
     */
    protected $usersService;
    /**
     * @var ShipsService
     */
    protected $shipsService;

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
        $redirectUrl = $request->headers->get('Referer');
        if (!$redirectUrl) {
            throw new BadRequestHttpException('Bad referrer');
        }

        $host = $this->applicationConfig->getWebHostname();
        $home = $host . '/';
        $login = $host . '/login';

        if (!$redirectUrl || $redirectUrl === $home || startsWith($login, (string)$redirectUrl)) {
            // don't send logged in users back to home or login. send them straight to the action
            $redirectUrl = $this->getDefaultRedirectUrl();
        }
        return $redirectUrl;
    }

    protected function getDefaultRedirectUrl(): string
    {
        return $this->applicationConfig->getWebHostname() . '/play';
    }

    protected function getLoginResponseForUser(User $user, string $url = null): Response
    {
        $cookie = $this->authenticationService->makeNewAuthenticationCookie($user);
        $url = $url ?? $this->getDefaultRedirectUrl();

        // if this is a brand new user, send them to the intro page
        if ($user->getRank()->isTutorial()) {
            $url = $this->applicationConfig->getWebHostname() . '/play/intro';
        }

        $response = new RedirectResponse($url);
        $response->headers->setCookie($cookie);
        return $this->noCacheResponse($response);
    }
}
