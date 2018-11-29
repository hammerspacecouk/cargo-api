<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\UserAuthenticationTrait;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\ShipsService;
use App\Service\UpgradesService;
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
    private $upgradesService;

    public static function getRouteDefinition(): array
    {
        return [
            static::class => new Route('/play/upgrades', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService,
        UpgradesService $upgradesService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
        $this->upgradesService = $upgradesService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);

        $data = [
            'ships' => $this->upgradesService->getAvailableShipsForUser($user),
            'repairs' => [],
            'weapons' => [],
            'navigation' => [],
        ];

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }
}
