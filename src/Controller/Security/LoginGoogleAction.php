<?php declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Service\TokensService;
use App\Service\UsersService;
use DateTimeImmutable;
use Google_Client;
use Google_Service_Oauth2;
use Psr\Log\LoggerInterface;
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
        TokensService $tokensService,
        Google_Client $client,
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
            return new RedirectResponse($client->createAuthUrl());
        }

        // check the code
        $logger->notice('[LOGIN] [GOOGLE]');
        $client->fetchAccessTokenWithAuthCode($code);

        $oauthClient = new Google_Service_Oauth2($client);
        $user = $oauthClient->userinfo_v2_me->get();

        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $tokensService->makeNewRefreshTokenCookie($user->email, $description);

        $response = new JsonResponse(['status' => 'ok']);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
