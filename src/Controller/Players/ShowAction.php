<?php
declare(strict_types=1);

namespace App\Controller\Players;

use App\Controller\IDRequestTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
use App\Service\AchievementService;
use App\Service\EventsService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShowAction
{
    use IDRequestTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/players/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => (new GenericValidator())->getPattern(),
        ]);
    }

    public function __construct(
        private AchievementService $achievementService,
        private EventsService $eventsService,
        private ShipsService $shipsService,
        private UsersService $usersService
    ) {
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        $uuid = $this->getIDFromUrl($request);
        $player = $this->usersService->getById($uuid);
        if (!$player) {
            throw new NotFoundHttpException('No such player');
        }

        $ships = $this->getShips($player);

        $r = new JsonResponse([
            'player' => $player,
            'fleet' => $ships,
            'events' => $this->eventsService->findLatestForUser($player),
            'missions' => array_values(
                array_filter($this->achievementService->findForUser($player), fn($a) => $a && $a->isCollected())
            ),
        ]);
        $r->setMaxAge(60 * 10);
        $r->setPublic();
        return $r;
    }

    private function getShips(User $player): array
    {
        $allShips = $this->shipsService->getForOwnerIDWithLocation($player->getId());

        // filter out destroyed ships
        $allShips = \array_filter($allShips, static function (Ship $ship) {
            return !$ship->isDestroyed();
        });

        // order the ships by name
        \usort($allShips, static function (Ship $a, Ship $b) {
            return $a->getName() <=> $b->getName();
        });

        return $allShips;
    }
}
