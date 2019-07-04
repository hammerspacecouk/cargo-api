<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Response\UpgradesResponse;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class UpgradesAction
{
    use UserAuthenticationTrait;

    protected $authenticationService;

    private $logger;
    private $upgradesResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/inventory', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        UpgradesResponse $upgradesResponse,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
        $this->upgradesResponse = $upgradesResponse;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);

        return $this->userResponse(
            new JsonResponse($this->upgradesResponse->getResponseDataForUser($user)),
            $this->authenticationService
        );
    }
}
