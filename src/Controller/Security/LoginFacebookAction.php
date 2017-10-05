<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\Facebook\PersistentDataHandler;
use App\Service\TokensService;
use App\Service\UsersService;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginFacebookAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        Facebook $client,
        PersistentDataHandler $dataHandler,
        UsersService $usersService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);

        $code = $request->get('code');

        $helper = $client->getRedirectLoginHelper();

        if (!$code) {
            $loginUrl = $helper->getLoginUrl(
                $applicationConfig->getApiHostname() . '/login/facebook',
                ['email']
            );
            $logger->notice('[LOGIN] [FACEBOOK REQUEST]');

            $response = new RedirectResponse($loginUrl);
            $response->headers->setCookie($dataHandler->makeCookie());
            return $response;
        }

        $logger->notice('[LOGIN] [FACEBOOK]');

        // we got a response, let's repopulate the cookie stuffs
        $dataHandler->setFromRequest($request);

        try {
            $accessToken = $helper->getAccessToken();
        } catch (FacebookResponseException | FacebookSDKException $e) {
            $logger->error($e->getMessage());
            throw new AccessDeniedHttpException('Error validating login');
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                $logger->error($helper->getErrorReason() . $helper->getErrorDescription());
                throw new UnauthorizedHttpException($helper->getError());
            } else {
                throw new BadRequestHttpException();
            }
        }

        // OAuth 2.0 client handler
        $oAuth2Client = $client->getOAuth2Client();

        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        $client->setDefaultAccessToken($longLivedAccessToken);

        $response = $client->get('/me?fields=email');
        $graphObject = $response->getGraphNode();
        $email = $graphObject->getField('email');

        if (empty($email)) {
            throw new UnauthorizedHttpException('You must have an e-mail address available to recognise you');
        }

        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $tokensService->makeNewRefreshTokenCookie($email, $description);

        $response = new RedirectResponse($applicationConfig->getWebHostname());
        $response->headers->setCookie($cookie);

        return $response;
    }
}
