<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\OAuth\SessionDataHandler;
use App\Service\TokensService;
use App\Service\UsersService;
use Exception;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginMicrosoftAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        Microsoft $client,
        SessionDataHandler $dataHandler,
        UsersService $usersService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);

        $code = $request->get('code');

        if (!$code) {
            $loginUrl = $client->getAuthorizationUrl();
            $dataHandler->set('state', $client->getState());
            $logger->notice('[LOGIN] [MICROSOFT REQUEST]');

            $response = new RedirectResponse($loginUrl);
            $response->headers->setCookie($dataHandler->makeCookie());
            return $response;
        }

        $logger->notice('[LOGIN] [MICROSOFT]');

        // we got a response, let's repopulate the cookie stuffs
        $dataHandler->setFromRequest($request);

        $state = $request->get('state');
        if (!$state || $state !== $dataHandler->get('state')) {
            throw new BadRequestHttpException('No state');
        }

        $token = $client->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $user = $client->getResourceOwner($token);
        $email = $user->getEmail();

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
