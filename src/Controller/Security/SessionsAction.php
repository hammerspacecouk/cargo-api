<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\UserAuthentication;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionsAction
{
    use UserAuthenticationTrait;

    private $authenticationService;
    private $logger;

    public function __construct(
        AuthenticationService $authenticationService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {
        $authentication = $this->getAuthentication($request, $this->authenticationService);
        $tokens = $this->authenticationService->findAllForUser($authentication->getUser());

        $sessions = array_map(function (UserAuthentication $token) use ($authentication) {
            $isCurrent = $token->getId()->equals($authentication->getId());

            return [
                'isCurrent' => $isCurrent,
                'removeToken' => $isCurrent? null : 'todo', // todo - make an action token based on the ID
                'state' => $token,
            ];
        }, $tokens);

        return $this->userResponse(new JsonResponse([
            'sessions' => $sessions
        ]), $this->authenticationService);
    }
}
