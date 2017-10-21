<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginMicrosoftAction extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        Microsoft $client
    ): Response {
        $this->logger->debug(__CLASS__);

        $code = $request->get('code');

        if (!$code) {
            $loginUrl = $client->getAuthorizationUrl();
            $this->flashData->set('state', $client->getState());
            $this->logger->notice('[LOGIN REQUEST] [MICROSOFT]');
            $this->setReturnAddress($request);
            return new RedirectResponse($loginUrl);
        }

        $this->logger->notice('[LOGIN] [MICROSOFT]');

        $state = $request->get('state');
        if (!$state || $state !== $this->flashData->getOnce('state')) {
            throw new BadRequestHttpException('No state');
        }

        $token = $client->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        /** @var MicrosoftResourceOwner $user */
        $user = $client->getResourceOwner($token);
        $email = $user->getEmail();

        if (empty($email)) {
            throw new UnauthorizedHttpException('You must have an e-mail address available to recognise you');
        }

        return $this->getLoginResponse($request, $email);
    }
}
