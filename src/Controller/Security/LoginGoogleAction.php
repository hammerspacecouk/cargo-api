<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Data\FlashDataStore;
use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ConfigService;
use App\Service\ShipsService;
use App\Service\UsersService;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Route;

class LoginGoogleAction extends AbstractLoginAction
{
    /**
     * @var string
     */
    private $googleOauthClientId;
    /**
     * @var string
     */
    private $googleOauthClientSecret;

    public static function getRouteDefinition(): Route
    {
        return new Route('/login/google', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ConfigService $configService,
        ShipsService $shipsService,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger,
        string $googleOauthClientId,
        string $googleOauthClientSecret
    ) {
        parent::__construct(
            $applicationConfig,
            $authenticationService,
            $configService,
            $shipsService,
            $flashData,
            $usersService,
            $logger,
            );
        $this->googleOauthClientId = $googleOauthClientId;
        $this->googleOauthClientSecret = $googleOauthClientSecret;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            throw new AccessDeniedHttpException('Correct credentials not supplied'); // todo - standard error format
        }

        $provider = new Google([
            'clientId' => $this->googleOauthClientId,
            'clientSecret' => $this->googleOauthClientSecret,
            'redirectUri' => $this->applicationConfig->getApiHostname() . '/login/google',
            // todo - state object with a verifiable token containing the redirect parameter
        ]);

        if (!$code) {
            $this->logger->notice('[LOGIN REQUEST] [GOOGLE]');
            $this->setReturnAddress($request);
            return new RedirectResponse($provider->getAuthorizationUrl());
        }

        // todo - verify the state token

        // check the code
        $this->logger->notice('[LOGIN] [GOOGLE]');
        /** @var AccessToken $token */
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
        try {
            /** @var GoogleUser $ownerDetails */
            $ownerDetails = $provider->getResourceOwner($token);
            $email = $ownerDetails->getEmail();

            if (!$email) {
                throw new \InvalidArgumentException('No e-mail found');
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->getLoginResponse($request, new EmailAddress($email));
    }
}
