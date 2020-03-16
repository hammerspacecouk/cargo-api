<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Domain\Entity\Achievement;
use App\Domain\ValueObject\SessionState;
use App\Response\FleetResponse;
use App\Service\AchievementService;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class IndexAction extends AbstractUserAction
{
    private $playerRanksService;
    private $fleetResponse;
    private $achievementService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AchievementService $achievementService,
        AuthenticationService $authenticationService,
        PlayerRanksService $playerRanksService,
        FleetResponse $fleetResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->playerRanksService = $playerRanksService;
        $this->fleetResponse = $fleetResponse;
        $this->achievementService = $achievementService;
    }

    public function invoke(
        Request $request
    ): array {
        $rankStatus = $this->playerRanksService->getForUser($this->user);
        $currentMissions = $this->achievementService->findForRank($this->user, $rankStatus->getCurrentRank());
        if (empty($currentMissions) && $rankStatus->getNextRank()) {
            $currentMissions = [
                Achievement::getPseudoMissionForPlanets($rankStatus)
            ];
        }

        return [
            'sessionState' => new SessionState(
                $this->user,
                $rankStatus,
            ),
            'fleet' => $this->fleetResponse->getResponseDataForUser($this->user),
            'currentMissions' => $currentMissions,
            'allMissions' => $this->achievementService->findForUser($this->user),
        ];
    }
}
