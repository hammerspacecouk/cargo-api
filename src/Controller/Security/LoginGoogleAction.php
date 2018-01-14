<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Domain\ValueObject\EmailAddress;
use Google_Client;
use Google_Service_Oauth2;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LoginGoogleAction extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        Google_Client $client
    ): Response {
        $this->logger->debug(__CLASS__);
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            throw new AccessDeniedHttpException('Correct credentials not supplied');
        }

        $client->setRedirectUri($this->applicationConfig->getApiHostname() . '/login/google');
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);

        if (!$code) {
            $this->logger->notice('[LOGIN REQUEST] [GOOGLE]');
            $this->setReturnAddress($request);
            return new RedirectResponse($client->createAuthUrl());
        }

        // check the code
        $this->logger->notice('[LOGIN] [GOOGLE]');
        $client->fetchAccessTokenWithAuthCode($code);

        $oauthClient = new Google_Service_Oauth2($client);
        $user = $oauthClient->userinfo_v2_me->get();

        return $this->getLoginResponse($request, new EmailAddress($user->email));
    }
}
