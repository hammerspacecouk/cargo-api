<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Config\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginTwitterAction
{
    use Traits\UserTokenTrait;

    private const RETURN_ADDRESS_KEY = 'ra';

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        TwitterOAuth $client,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);

        $token = $request->get('oauth_token');
        $verifier = $request->get('oauth_verifier');
        if (!$token) {
            $logger->notice('[LOGIN] [TWITTER REQUEST]');
            $requestToken = $client->oauth(
                'oauth/request_token',
                [
                    'oauth_callback' => $applicationConfig->getApiHostname() . '/login/twitter'
                ]
            );

            $url = $client->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));
            $referrer = $request->headers->get('Referer');
            $flashData->set(self::RETURN_ADDRESS_KEY, $referrer);
            return new RedirectResponse($url);
        }

        $client->setOauthToken($token, $verifier);

        $accessToken = $client->oauth(
            'oauth/access_token',
            [
                'oauth_verifier' => $verifier,
                'oauth_token'=> $token,
            ]
        );

        $client->setOauthToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

        $userDetails = $client->get('account/verify_credentials', ['include_email' => 'true']);

        if (empty($userDetails->email)) {
            throw new UnauthorizedHttpException('Could not find an e-mail address from your Twitter details');
        }

        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $tokensService->makeNewRefreshTokenCookie($userDetails->email, $description);

        $returnUrl = $flashData->getOnce(self::RETURN_ADDRESS_KEY) ?? $applicationConfig->getWebHostname();

        $response = new RedirectResponse($returnUrl);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
