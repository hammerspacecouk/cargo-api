<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ShipsService;
use App\Service\UsersService;
use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function App\Functions\Strings\startsWith;

abstract class AbstractLoginAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    protected $applicationConfig;
    protected $authenticationService;
    protected $usersService;
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

        // if this is a brand new user, send them to their first ship
        if ($user->getScore()->getScore() === 0) { // new users have no score (cheap check)
            /** @var Ship[] $ships */
            $ships = $this->shipsService->getForOwnerIDWithLocation($user->getId(), 1);
            if (isset($ships[0])) {
                $url = $this->applicationConfig->getWebHostname() . '/play/' . $ships[0]->getId()->toString();
            }
        }

        $response = new RedirectResponse($url);
        $response->headers->setCookie($cookie);
        return $this->noCacheResponse($response);
    }
}
