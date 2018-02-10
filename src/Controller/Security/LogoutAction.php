<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Infrastructure\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Domain\ValueObject\Message\Info;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $applicationConfig;
    private $flashData;
    private $logger;

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig,
        FlashDataStore $flashData,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
        $this->flashData = $flashData;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->clearAuthentication($request, $this->authenticationService, $this->logger);

        // destroy all flash cookies
        $this->flashData->destroy();

        // set an ok message
        $this->flashData->addMessage(new Info('Logged out'));

        $response = new RedirectResponse($this->applicationConfig->getWebHostname());

        // redirect to the application homepage, now that you're logged out
        return $this->userResponse($response, $this->authenticationService);
    }
}
