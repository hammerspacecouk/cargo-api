<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
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
        $user = $this->usersService->getOrCreateByEmailAddress($emailAddress);

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

        if (!$redirectUrl || $redirectUrl === $home) {
            // don't send logged in users back to home. send them straight to the action
            $redirectUrl = $host . '/play';
        }

        return $redirectUrl;
    }
}
