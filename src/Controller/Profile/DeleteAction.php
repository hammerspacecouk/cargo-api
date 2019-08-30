<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
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
use Symfony\Component\Routing\Route;

class DeleteAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    private $authenticationService;
    private $applicationConfig;
    private $logger;
    private $usersService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile/delete', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
        $this->logger = $logger;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): Response {
        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedHttpException(['POST']);
        }
        $token = $request->get('token');
        if ($token === null) {
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
        $user = $this->getUser($request, $this->authenticationService);
        if (!$this->usersService->canUserDelete($user)) {
            throw new BadRequestHttpException('Tried to delete without being allowed. Tut tut');
        }

        $nextStage = 2;
        $stageTwoToken = $this->usersService->makeDeleteAccountToken($user->getId(), $nextStage);

        return $this->makeTokenRedirect($nextStage, $stageTwoToken);
    }

    private function handleSecondScreen(DeleteAccountToken $token, Request $request): Response
    {
        $this->logger->notice('[ACCOUNT DELETE] [STAGE 2]');
        // second screen will ensure you got through our token check, ensuring it's not already used
        $user = $this->getUser($request, $this->authenticationService);
        if (!$this->usersService->canUserDelete($user)) {
            throw new BadRequestHttpException('Tried to delete without being allowed. Tut tut');
        }

        $stageThreeToken = $this->usersService->useStageTwoDeleteAccountToken($token);

        return $this->makeTokenRedirect($stageThreeToken->getStage(), $stageThreeToken);
    }

    private function handleFinalDeletion(DeleteAccountToken $token, Request $request): Response
    {
        $this->logger->notice('[ACCOUNT DELETE] [STAGE 3]');
        // the final screen will check your session matches the user in the token and delete the account

        $user = $this->getUser($request, $this->authenticationService);
        if (!$user->getId()->equals($token->getUserId())) {
            throw new BadRequestHttpException('Token not for this user');
        }
        if (!$this->usersService->canUserDelete($user)) {
            throw new BadRequestHttpException('Tried to delete without being allowed. Tut tut');
        }

        $this->usersService->useStageThreeDeleteAccountToken($token);
        $logoutResponse = $this->logoutResponse($this->applicationConfig, $this->authenticationService);
        return $this->noCacheResponse($logoutResponse);
    }

    private function makeTokenRedirect($stage, $token): Response
    {
        $params = [
            'stage' => $stage,
            'token' => (string)$token,
        ];

        $response = new RedirectResponse(
            $this->applicationConfig->getWebHostname() .
            '/delete?' .
            http_build_query($params),
        );
        return $this->noCacheResponse($response);
    }
}
