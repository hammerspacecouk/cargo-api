<?php
declare(strict_types=1);

namespace App\Controller\Players;

use App\Controller\IDRequestTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;
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

    private UsersService $usersService;
    private ShipsService $shipsService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/players/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => (new GenericValidator())->getPattern(),
        ]);
    }

    public function __construct(
        ShipsService $shipsService,
        UsersService $usersService
    ) {
        $this->usersService = $usersService;
        $this->shipsService = $shipsService;
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
