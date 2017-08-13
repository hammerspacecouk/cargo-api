<?php
declare(strict_types = 1);
namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Config\TokenConfig;
use App\Data\TokenHandler;
use App\Domain\ValueObject\Token\UserIDToken;
use App\Service\UsersService;
use Google_Client;
use Google_Service_Oauth2;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LoginGoogleAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokenConfig $tokenConfig,
        TokenHandler $tokensHandler,
        Google_Client $client,
        UsersService $usersService
    ): Response {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            throw new AccessDeniedHttpException('Correct credentials not supplied');
        }

        $client->setRedirectUri($applicationConfig->getHostname() . '/login/google');
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);

        if (!$code) {
            return new RedirectResponse($client->createAuthUrl());
        }

        // check the code
        $client->fetchAccessTokenWithAuthCode($code);

        $oauthClient = new Google_Service_Oauth2($client);
        $user = $oauthClient->userinfo_v2_me->get();

        $email = $user->email;
        $user = $usersService->getOrCreateUserByEmail($email);

        // todo - figure out refresh token vs access token logic
        $token = $tokensHandler->makeToken(
            UserIDToken::makeClaims($user->getId()),
            TokenHandler::EXPIRY_ONE_DAY
        );

        $response = new JsonResponse([
            'token' => (string) $token
        ]);
        $response->headers->setCookie($tokensHandler->makeRefreshTokenCookie($token));

        return $response;
    }
}
