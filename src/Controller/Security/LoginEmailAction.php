<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Data\FlashDataStore;
use App\Domain\Exception\InvalidEmailAddressException;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\EmailsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginEmailAction extends AbstractLoginAction
{
    private $emailsService;

    public function __construct(
        EmailsService $emailsService,
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        FlashDataStore $flashData,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $applicationConfig,
            $authenticationService,
            $flashData,
            $usersService,
            $logger
        );
        $this->emailsService = $emailsService;
    }

    public function __invoke(
        Request $request
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
            return $this->sendEmail($request, $target);
        }
        throw new BadRequestHttpException('Expecting an e-mail address or token');
    }

    private function processLogin(
        Request $request,
        string $token
    ): Response {
        try {
            $token = $this->authenticationService->useEmailLoginToken($token);
        } catch (TokenException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage());
        }
        return $this->getLoginResponse($request, $token->getEmailAddress());
    }

    private function sendEmail(
        Request $request,
        string $emailAddress
    ) {
        $this->setReturnAddress($request);
        try {
            $emailAddress = new EmailAddress($emailAddress);
            $token = $this->authenticationService->makeEmailLoginToken($emailAddress);

            $this->emailsService->sendLoginEmail($emailAddress, $token);
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
