<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Domain\ValueObject\SessionState;
use App\Response\FleetResponse;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class IndexAction extends AbstractUserAction
{
    private $playerRanksService;
    private $fleetResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        FleetResponse $fleetResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->playerRanksService = $playerRanksService;
        $this->fleetResponse = $fleetResponse;
    }

    public function invoke(
        Request $request
    ): array {
        return [
            'sessionState' => new SessionState(
                $this->user,
                $this->playerRanksService->getForUser($this->user)
            ),
            'fleet' => $this->fleetResponse->getResponseDataForUser($this->user),
        ];
    }
}
