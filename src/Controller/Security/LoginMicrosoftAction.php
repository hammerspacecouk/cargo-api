<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginMicrosoftAction
{
    use Traits\UserTokenTrait;

    private const RETURN_ADDRESS_KEY = 'ra';

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        Microsoft $client,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);

        $code = $request->get('code');

        if (!$code) {
            $loginUrl = $client->getAuthorizationUrl();
            $flashData->set('state', $client->getState());
            $logger->notice('[LOGIN] [MICROSOFT REQUEST]');

            $referrer = $request->headers->get('Referer');
            $flashData->set(self::RETURN_ADDRESS_KEY, $referrer);
            $response = new RedirectResponse($loginUrl);
            return $response;
        }

        $logger->notice('[LOGIN] [MICROSOFT]');

        $state = $request->get('state');
        if (!$state || $state !== $flashData->getOnce('state')) {
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

        $description = $request->headers->get('User-Agent', 'Unknown');

        $cookie = $tokensService->makeNewRefreshTokenCookie($email, $description);

        $returnUrl = $flashData->getOnce(self::RETURN_ADDRESS_KEY) ?? $applicationConfig->getWebHostname();

        $response = new RedirectResponse($returnUrl);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
