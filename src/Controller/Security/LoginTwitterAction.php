<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Domain\ValueObject\EmailAddress;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginTwitterAction extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        TwitterOAuth $client
    ): Response {
        $this->logger->debug(__CLASS__);

        $token = $request->get('oauth_token');
        $verifier = $request->get('oauth_verifier');
        if (!$token) {
            $requestToken = $client->oauth(
                'oauth/request_token',
                [
                    'oauth_callback' => $this->applicationConfig->getApiHostname() . '/login/twitter',
                ]
            );

            $loginUrl = $client->url('oauth/authorize', ['oauth_token' => $requestToken['oauth_token']]);
            $this->logger->notice('[LOGIN REQUEST] [TWITTER]');
            $this->setReturnAddress($request);
            return new RedirectResponse($loginUrl);
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

        $userDetails = (array)$client->get('account/verify_credentials', ['include_email' => 'true']);

        if (empty($userDetails['email'])) {
            throw new UnauthorizedHttpException('Could not find an e-mail address from your Twitter details');
        }

        return $this->getLoginResponse($request, new EmailAddress($userDetails['email']));
    }
}
