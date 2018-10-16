<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Domain\ValueObject\EmailAddress;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginFacebookAction extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        Facebook $client
    ): Response {
        $this->logger->debug(__CLASS__);

        $code = $request->get('code');

        $helper = $client->getRedirectLoginHelper();

        if (!$code) {
            $loginUrl = $helper->getLoginUrl(
                $this->applicationConfig->getApiHostname() . '/login/facebook',
                ['email']
            );
            $this->logger->notice('[LOGIN REQUEST] [FACEBOOK]');
            $this->setReturnAddress($request);
            return new RedirectResponse($loginUrl);
        }

        $this->logger->notice('[LOGIN] [FACEBOOK]');
        try {
            $accessToken = $helper->getAccessToken();
        } catch (FacebookResponseException | FacebookSDKException $e) {
            $this->logger->error($e->getMessage());
            throw new AccessDeniedHttpException('Error validating login');
        }

        if ($accessToken === null) {
            if ($helper->getError()) {
                $this->logger->error($helper->getErrorReason() . $helper->getErrorDescription());
                throw new UnauthorizedHttpException($helper->getError());
            }
            throw new BadRequestHttpException();
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

        return $this->getLoginResponse($request, new EmailAddress($email));
    }
}
