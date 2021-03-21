<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\Oauth\OAuthServiceInterface;
use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OAuthHandler extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        AbstractProvider $oauthProvider,
        OAuthServiceInterface $oAuthService
    ): Response {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            throw new AccessDeniedHttpException('Correct credentials not supplied'); // todo - standard error format
        }

        if (!$code) {
            $url = $oauthProvider->getAuthorizationUrl();
            $stateId = $oauthProvider->getState();
            $stateCookie = $this->authenticationService->getOAuthStateCookie($stateId, $this->getRedirectUrl($request));

            $response = new RedirectResponse($url);
            $response->headers->setCookie($stateCookie);
            return $response;
        }

        // check the code
        try {
            $state = $this->authenticationService->parseOauthState($request);
            /** @var AccessToken $token */
            $token = $oauthProvider->getAccessToken('authorization_code', ['code' => $code,]);
            $ownerId = (string)$oauthProvider->getResourceOwner($token)->getId();
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        // do you already have an anonymous session
        $currentSessionUser = $this->getUserIfExists($request, $this->authenticationService);
        if ($currentSessionUser) {
            // if this is a current user we can attach the oauth to this account
            // but ONLY if there isn't already an account with this id
            if ($oAuthService->userExistsForOAuthId($ownerId)) {
                return new RedirectResponse(
                    $this->applicationConfig->getWebHostname() . '/about/duplicate'
                );
            }
            $user = $oAuthService->attachToUser($currentSessionUser, $ownerId);
        } else {
            throw new AccessDeniedHttpException('Sorry. New users are not being accepted at this time');
        }
        return $this->getLoginResponseForUser($user, $state->getReturnUrl());
    }
}
