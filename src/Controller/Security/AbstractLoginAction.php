<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\User;
use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractLoginAction
{
    use UserAuthenticationTrait;

    protected const RETURN_ADDRESS_KEY = 'ra';

    protected $applicationConfig;
    protected $authenticationService;
    protected $flashData;
    protected $usersService;
    protected $logger;

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->authenticationService = $authenticationService;
        $this->flashData = $flashData;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    // todo - method to remove any previously set authentication_tokens if you try to login again

    protected function setReturnAddress(
        Request $request
    ): void {
        $referrer = $request->headers->get('Referer');
        // Adds to the flashData bag - which is set as a response cookie in the Kernel Listener
        $this->flashData->set(self::RETURN_ADDRESS_KEY, $referrer);
    }

    protected function getLoginResponse(
        Request $request,
        EmailAddress $emailAddress
    ): RedirectResponse {
        // do you already have an anonymous session
        $currentSessionUser = $this->getUserIfExists($request, $this->authenticationService);
        if ($currentSessionUser && !$currentSessionUser->hasEmailAddress()) {
            // if this is an anonymous user we can convert it to a full account
            // but ONLY if there isn't already an account with this email
            $exists = (bool)$this->usersService->getByEmailAddress($emailAddress);
            if ($exists) {
                return new RedirectResponse(
                    $this->applicationConfig->getWebHostname() . '/about/duplicate'
                );
            }
            $user = $this->usersService->addEmailToUser($currentSessionUser, $emailAddress);
        } else {
            $user = $this->usersService->getOrCreateByEmailAddress($emailAddress);
        }

        return $this->getLoginResponseForUser($user);
    }

    protected function getLoginResponseForUser(User $user): RedirectResponse
    {
        $cookie = $this->authenticationService->makeNewAuthenticationCookie($user);

        $response = new RedirectResponse($this->getRedirectUrl());
        $response->headers->setCookie($cookie);

        return $response;
    }

    private function getRedirectUrl()
    {
        $redirectUrl = $this->flashData->getOnce(self::RETURN_ADDRESS_KEY);
        $host = $this->applicationConfig->getWebHostname();
        $home = $host . '/';
        $login = $host . '/login';

        if (!$redirectUrl || $redirectUrl === $home || \strpos($redirectUrl, $login) === 0) {
            // don't send logged in users back to home or login. send them straight to the action
            $redirectUrl = $host . '/play';
        }

        return $redirectUrl;
    }
}
