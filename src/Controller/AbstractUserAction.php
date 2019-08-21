<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractUserAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    protected $authenticationService;
    /** @var User */
    protected $user;
    protected $logger;

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
        $this->logger->debug('[USER_ACTION] ' . static::class);
        $this->user = $this->getUser($request, $this->authenticationService);
        $this->logger->debug('[USER_ID] ' . $this->user->getId()->toString());
        $responseData = $this->invoke($request);
        return $this->noCacheResponse(new JsonResponse($responseData));
    }

    abstract public function invoke(Request $request): array;
}
