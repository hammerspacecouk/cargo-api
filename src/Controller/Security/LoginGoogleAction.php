<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Service\TokensService;
use App\Service\UsersService;
use Google_Client;
use Google_Service_Oauth2;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LoginGoogleAction
{
    use Traits\UserTokenTrait;

    private const RETURN_ADDRESS_KEY = 'ra';

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        Google_Client $client,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            throw new AccessDeniedHttpException('Correct credentials not supplied');
        }

        $client->setRedirectUri($applicationConfig->getApiHostname() . '/login/google');
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);

        if (!$code) {
            $logger->notice('[LOGIN] [GOOGLE REQUEST]');
            $referrer = $request->headers->get('Referer');
            $flashData->set(self::RETURN_ADDRESS_KEY, $referrer);
            return new RedirectResponse($client->createAuthUrl());
        }

        // check the code
        $logger->notice('[LOGIN] [GOOGLE]');
        $client->fetchAccessTokenWithAuthCode($code);

        $oauthClient = new Google_Service_Oauth2($client);
        $user = $oauthClient->userinfo_v2_me->get();

        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $tokensService->makeNewRefreshTokenCookie($user->email, $description);

        $returnUrl = $flashData->getOnce(self::RETURN_ADDRESS_KEY) ?? $applicationConfig->getWebHostname();

        $response = new RedirectResponse($returnUrl);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
