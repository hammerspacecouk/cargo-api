<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Data\FlashDataStore;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ConfigService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function App\Functions\Strings\startsWith;

class AbstractLoginAction
{
    use UserAuthenticationTrait;

    protected const RETURN_ADDRESS_KEY = 'ra';

    protected $applicationConfig;
    protected $authenticationService;
    protected $flashData;
    protected $usersService;
    protected $shipsService;
    protected $logger;
    private $configService;

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ConfigService $configService,
        ShipsService $shipsService,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->authenticationService = $authenticationService;
        $this->flashData = $flashData;
        $this->usersService = $usersService;
        $this->logger = $logger;
        $this->shipsService = $shipsService;
        $this->configService = $configService;
    }

    // todo - method to remove any previously set authentication_tokens if you try to login again

    protected function setReturnAddress( // todo - store this in the STATE param
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
        if ($currentSessionUser && $currentSessionUser->isAnonymous()) {
            // if this is an anonymous user we can convert it to a full account
            // but ONLY if there isn't already an account with this email
            // todo - this logic changes with the oauth changes
            $exists = (bool)$this->usersService->getByEmailAddress($emailAddress);
            if ($exists) {
                return new RedirectResponse(
                    $this->applicationConfig->getWebHostname() . '/about/duplicate'
                );
            }
            $user = $this->usersService->addEmailToUser($currentSessionUser, $emailAddress);
        } else {
            // START CODE FOR ALPHA PHASE - todo - remove for beta
            if (!$this->configService->emailExistsInAlphaList((string)$emailAddress)) {
                throw new BadRequestHttpException('You are not yet allowed in the alpha');
            }
            // END CODE FOR ALPHA PHASE - todo - remove for beta


            $user = $this->usersService->getOrCreateByEmailAddress($emailAddress);
        }

        return $this->getLoginResponseForUser($user);
    }

    protected function getLoginResponseForUser(User $user): RedirectResponse
    {
        $cookie = $this->authenticationService->makeNewAuthenticationCookie($user);
        $url = $this->getRedirectUrl();

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

        return $response;
    }

    private function getRedirectUrl()
    {
        $redirectUrl = $this->flashData->getOnce(self::RETURN_ADDRESS_KEY);
        $host = $this->applicationConfig->getWebHostname();
        $home = $host . '/';
        $login = $host . '/login';

        if (!$redirectUrl || $redirectUrl === $home || startsWith($login, (string)$redirectUrl)) {
            // don't send logged in users back to home or login. send them straight to the action
            $redirectUrl = $host . '/play';
        }

        return $redirectUrl;
    }
}
