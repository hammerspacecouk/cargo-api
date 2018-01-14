<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\Security\Traits\CsrfTokenTrait;
use App\Domain\Exception\InvalidEmailAddressException;
use App\Domain\ValueObject\EmailAddress;
use App\Service\EmailsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginEmailAction extends AbstractLoginAction
{
    use CsrfTokenTrait;

    public const CRSF_CONTEXT_KEY = 'login';

    public function __invoke(
        Request $request,
        EmailsService $emailsService
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
            return $this->sendEmail($request, $emailsService, $target);
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
        EmailsService $emailsService,
        string $emailAddress
    ) {
        $this->setReturnAddress($request);
        try {
            $this->checkCsrfToken($request, self::CRSF_CONTEXT_KEY, $this->tokensService, $this->logger);

            $emailAddress = new EmailAddress($emailAddress);
            $emailsService->sendLoginEmail($emailAddress);
        } catch (InvalidEmailAddressException | BadRequestHttpException $e) {
            // todo - set the error to the flash message. Setup a Messages object
            return new RedirectResponse($this->applicationConfig->getWebHostname() . '/login?mailfail');
        }


        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['status' => 'ok']);
        }
        // todo - differing response for XHR
        return new RedirectResponse($this->applicationConfig->getWebHostname() . '/login?mailsent');
    }
}
