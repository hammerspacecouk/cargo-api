<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Data\FlashDataStore;
use App\Domain\Exception\InvalidEmailAddressException;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Message\Error;
use App\Domain\ValueObject\Message\Messages;
use App\Domain\ValueObject\Message\Ok;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\ConfigService;
use App\Service\EmailsService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Route;

class LoginEmailAction extends AbstractLoginAction
{
    public const TOKEN_TYPE = 'loginEmail';

    private $emailsService;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/login/email', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        EmailsService $emailsService,
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        ConfigService $configService,
        FlashDataStore $flashData,
        UsersService $usersService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $applicationConfig,
            $authenticationService,
            $configService,
            $shipsService,
            $flashData,
            $usersService,
            $logger,
        );
        $this->emailsService = $emailsService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $token = $request->get('token');
        $target = $request->get('target');

        try {
            if ($target && $this->usersService->verifyLoginToken($token, self::TOKEN_TYPE)) {
                $this->logger->notice('[LOGIN REQUEST] [EMAIL]');
                return $this->sendEmail($request, $target);
            }

            if ($token) {
                $this->logger->notice('[LOGIN] [EMAIL]');
                return $this->processLogin($request, $token);
            }
        } catch (TokenException | InvalidEmailAddressException | BadRequestHttpException $e) {
            $query = '?messages=' . new Messages([new Error($e->getMessage())]);
            return new RedirectResponse(
                $this->applicationConfig->getWebHostname() . '/login' . $query
            );
        }
        throw new BadRequestHttpException('Expecting an e-mail address or valid token');
    }

    private function processLogin(
        Request $request,
        string $token
    ): Response {
        $parsedToken = $this->authenticationService->useEmailLoginToken($token);
        return $this->getLoginResponse($request, $parsedToken->getEmailAddress());
    }

    private function sendEmail(
        Request $request,
        string $emailAddress
    ) {
        $this->setReturnAddress($request);
        $validEmailAddress = new EmailAddress($emailAddress);
        $token = $this->authenticationService->makeEmailLoginToken($validEmailAddress);

        $this->emailsService->sendLoginEmail($validEmailAddress, $token);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['status' => 'ok']);
        }
        $query = '?messages=' . new Messages([new Ok('Sent. Please check your e-mail for the login link'),]);
        return new RedirectResponse($this->applicationConfig->getWebHostname() . '/login' . $query);
    }
}
