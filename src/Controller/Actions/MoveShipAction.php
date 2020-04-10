<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Entity\Ship;
use App\Domain\ValueObject\Direction;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Response\ShipInChannelResponse;
use App\Response\ShipInPortResponse;
use App\Service\EffectsService;
use App\Service\ShipLocationsService;
use App\Service\Ships\ShipMovementService;
use App\Service\UsersService;
use function App\Functions\Arrays\filteredMap;
use function App\Functions\Arrays\find;

class MoveShipAction
{
    private ShipMovementService $shipMovementService;
    private UsersService $usersService;
    private ShipInChannelResponse $shipInChannelResponse;
    private EffectsService $effectsService;
    private ShipInPortResponse $shipInPortResponse;
    private ShipLocationsService $shipLocationsService;

    public function __construct(
        ShipLocationsService $shipLocationsService,
        ShipMovementService $shipMovementService,
        UsersService $usersService,
        EffectsService $effectsService,
        ShipInChannelResponse $shipInChannelResponse,
        ShipInPortResponse $shipInPortResponse
    ) {
        $this->shipMovementService = $shipMovementService;
        $this->usersService = $usersService;
        $this->shipInChannelResponse = $shipInChannelResponse;
        $this->effectsService = $effectsService;
        $this->shipInPortResponse = $shipInPortResponse;
        $this->shipLocationsService = $shipLocationsService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $moveShipToken = $this->shipMovementService->parseMoveShipToken($tokenString);
        $ship = $this->shipMovementService->getByID($moveShipToken->getShipId());
        if (!$ship) {
            throw new \RuntimeException('Something went very wrong. Ship was not found');
        }
        $user = $this->usersService->getById($moveShipToken->getOwnerId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong. User was not found');
        }

        // all over ships in convoy must now move
        $convoyTokens = [];
        if ($ship->isInConvoy()) {
            $convoyTokens = filteredMap(
                // get the response for each ship in the convoy in order to use them now
                $this->shipMovementService->findAllInConvoy($ship->getConvoyId()),
                function(Ship $convoyShip) use ($user, $ship, $moveShipToken) {
                    if ($convoyShip->getId()->equals($ship->getId())) {
                        return null;
                    }

                    // find the relevant token
                    $result = $this->shipInPortResponse->getResponseData(
                        $user,
                        $convoyShip,
                        $this->shipLocationsService->getCurrentForShip($convoyShip),
                    );

                    $directions = $result['directions'];
                    // find the direction that matches the current
                    $direction = find(static function($direction) use ($moveShipToken) {
                        if (!isset($direction['detail'])) {
                            return false;
                        }
                        /** @var Direction $detail */
                        $detail = $direction['detail'];
                        return $detail->getChannel()->getId()->equals($moveShipToken->getChannelId());
                    }, $directions);

                    return $direction['action'];
                }
            );
        }

        $newChannelLocation = $this->shipMovementService->useMoveShipToken($moveShipToken, $convoyTokens);

        // send back the new state of the ship in the channel and the new state of the user

        return [
            'data' => $this->shipInChannelResponse->getResponseData(
                $user,
                $this->shipMovementService->getByID($moveShipToken->getShipId()),
                $newChannelLocation,
                $this->effectsService->addRandomEffectsForUser($user)
            ),
            'error' => null,
        ];
    }
}
