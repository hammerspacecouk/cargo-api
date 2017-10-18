<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractLoginAction
{
    use Traits\UserTokenTrait;

    protected const RETURN_ADDRESS_KEY = 'ra';

    protected $applicationConfig;
    protected $tokensService;
    protected $flashData;
    protected $usersService;
    protected $logger;

    public function __construct(
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->tokensService = $tokensService;
        $this->flashData = $flashData;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    protected function setReturnAddress(
        Request $request
    ) {
        $referrer = $request->headers->get('Referer');
        // Adds to the flashData bag - which is set as a response cookie in the Kernel Listener
        $this->flashData->set(self::RETURN_ADDRESS_KEY, $referrer);
    }

    protected function getLoginResponse(
        Request $request,
        string $emailAddress
    ) {
        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $this->tokensService->makeNewRefreshTokenCookie($emailAddress, $description);

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
