<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginEmailAction extends AbstractLoginAction
{
    public function __invoke(
        Request $request,
        Swift_Mailer $mailer
    ): Response {
        $this->logger->debug(__CLASS__);
        $token = $request->get('token');
        $target = $request->get('target');

        if ($token) {
            $this->logger->notice('[LOGIN] [EMAIL]');
            return $this->processLogin($request, $token);
        }
        if ($target) {
            $this->logger->notice('[LOGIN REQUEST] [EMAIL]');
            return $this->sendEmail($request, $target, $mailer);
        }

        throw new BadRequestHttpException('Expecting an e-mail address or token');
    }

    private function processLogin(
        Request $request,
        string $token
    ) {
        $token = $this->tokensService->parseEmailLoginToken($token);
        return $this->getLoginResponse($request, $token->getEmailAddress());
    }

    private function sendEmail(
        Request $request,
        string $emailAddress,
        Swift_Mailer $mailer
    ) {
        // todo - handle it differently it's an XHR request
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Not a valid e-mail address');
        }

        $token = $this->tokensService->getEmailLoginToken($emailAddress);

        // todo - move this to an e-mail service or something
        $url = $this->applicationConfig->getApiHostname() . '/login/email?token=' . (string)$token;
        $body = <<<EMAIL
<p>This link will work for 1 hour and will log you in</p>
<p><a href="$url">$url</a></p>
EMAIL;

        $message = new Swift_Message(
            'Login link',
            $body,
            'text/html'
        );
        $message->addFrom(
            $this->applicationConfig->getEmailFromAddress(),
            $this->applicationConfig->getEmailFromName()
        );
        $message->addTo($emailAddress);

        $mailer->send($message);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['status' => 'ok']);
        }

        // todo - differing response for XHR
        $this->setReturnAddress($request);
        return new RedirectResponse($this->applicationConfig->getWebHostname() . '/login?mailsent');
    }
}
