<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Service\TokensService;
use App\Service\UsersService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginEmailAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        UsersService $usersService,
        Swift_Mailer $mailer,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ): Response {
        $logger->debug(__CLASS__);
        $token = $request->get('token');
        $target = $request->get('target');

        if ($token) {
            $logger->notice('[LOGIN] [EMAIL]');
            return $this->processLogin($request, $token, $tokensService, $applicationConfig);
        }
        if ($target) {
            $logger->notice('[LOGIN] [EMAIL_REQUEST]');
            return $this->sendEmail($request, $target, $tokensService, $mailer, $applicationConfig);
        }

        throw new BadRequestHttpException('Expecting an e-mail address or token');
    }

    private function processLogin(
        Request $request,
        string $token,
        TokensService $tokensService,
        ApplicationConfig $applicationConfig
    ) {
        $description = $request->headers->get('User-Agent', 'Unknown');

        $token = $tokensService->parseEmailLoginToken($token);

        // todo - figure out a new user, to assign them ships and locations
        $cookie = $tokensService->makeNewRefreshTokenCookie($token->getEmailAddress(), $description);

        $redirectAddress = $token->getReturnAddress() ?? $applicationConfig->getWebHostname();

        $response = new RedirectResponse($redirectAddress);
        $response->headers->setCookie($cookie);

        return $response;
    }

    private function sendEmail(
        Request $request,
        string $emailAddress,
        TokensService $tokensService,
        Swift_Mailer $mailer,
        ApplicationConfig $applicationConfig
    ) {
        $referrer = $request->headers->get('Referer');

        // todo - handle it differently it's an XHR request
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Not a valid e-mail address');
        }

        $token = $tokensService->getEmailLoginToken($emailAddress, $referrer);


        // todo - move this to an e-mail service or something
        $url = $applicationConfig->getApiHostname() . '/login/email?token=' . (string)$token;
        $body = <<<EMAIL
<p>This link will work for 1 hour and will log you in</p>
<p><a href="$url">$url</a></p>
EMAIL;

        $message = new Swift_Message(
            'Login link',
            $body,
            'text/html'
        );
        $message->addFrom($applicationConfig->getEmailFromAddress(), $applicationConfig->getEmailFromName());
        $message->addTo($emailAddress);

        $mailer->send($message);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['status' => 'ok']);
        }

        // todo - differing response for XHR
        return new RedirectResponse($applicationConfig->getWebHostname() . '/login?mailsent');
    }
}
