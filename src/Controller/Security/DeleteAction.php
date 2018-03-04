<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Data\FlashDataStore;
use App\Domain\ValueObject\Token\DeleteAccountToken;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class DeleteAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $applicationConfig;
    private $flashData;
    private $logger;
    private $usersService;

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig,
        UsersService $usersService,
        FlashDataStore $flashData,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
        $this->flashData = $flashData;
        $this->logger = $logger;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): Response {
        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedHttpException(['POST']);
        }
        $token = $request->get('token', null);
        if (!isset($token)) {
            throw new BadRequestHttpException('Missing Token Parameter');
        }

        if (empty($token)) {
            return $this->handleFirstScreen($request);
        }

        // parse the token to check if it is second screen or third
        $deleteToken = $this->usersService->parseDeleteAccountToken($token);

        switch ($deleteToken->getStage()) {
            case 2:
                return $this->handleSecondScreen($deleteToken, $request);
            case 3:
                return $this->handleFinalDeletion($deleteToken, $request);
        }

        throw new BadRequestHttpException('Invalid Token');
    }

    private function handleFirstScreen(Request $request): Response
    {
        $this->logger->notice('[ACCOUNT DELETE] [STAGE 1]');

        // first screen must check the session to get your user in the first place
        $user = $this->getAuthentication($request, $this->authenticationService)->getUser();

        $nextStage = 2;
        $stageTwoToken = $this->usersService->makeDeleteAccountToken($user->getId(), $nextStage);

        return $this->makeTokenRedirect($nextStage, $stageTwoToken);
    }

    private function handleSecondScreen(DeleteAccountToken $token, Request $request): Response
    {
        $this->logger->notice('[ACCOUNT DELETE] [STAGE 2]');
        // second screen will ensure you got through our token check, ensuring it's not already used
        $this->getAuthentication($request, $this->authenticationService)->getUser();

        $stageThreeToken = $this->usersService->useStageTwoDeleteAccountToken($token);

        return $this->makeTokenRedirect($stageThreeToken->getStage(), $stageThreeToken);
    }

    private function handleFinalDeletion(DeleteAccountToken $token, Request $request): Response
    {
        $this->logger->notice('[ACCOUNT DELETE] [STAGE 3]');
        // the final screen will check your session matches the user in the token and delete the account

        $user = $this->getAuthentication($request, $this->authenticationService)->getUser();
        if (!$user->getId()->equals($token->getUserId())) {
            throw new BadRequestHttpException('Token not for this user');
        }

        $this->usersService->useStageThreeDeleteAccountToken($token);
        $this->clearAuthentication($request, $this->authenticationService, $this->logger);

        $response = new RedirectResponse($this->applicationConfig->getWebHostname());

        // redirect to the application homepage, now that the account is deleted and you're logged out
        return $this->userResponse($response, $this->authenticationService);
    }

    private function makeTokenRedirect($stage, $token)
    {
        $params = [
            'stage' => $stage,
            'token' => (string) $token,
        ];

        $response = new RedirectResponse(
            $this->applicationConfig->getWebHostname() .
            '/profile/delete?' .
            http_build_query($params)
        );

        // redirect to the application homepage, now that the account is deleted and you're logged out
        return $this->userResponse($response, $this->authenticationService);
    }
}
