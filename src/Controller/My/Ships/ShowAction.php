<?php
declare(strict_types = 1);
namespace App\Controller\My\Ships;

use App\Config\ApplicationConfig;
use App\Config\TokenConfig;
use App\Controller\PaginationRequestTrait;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Service\ChannelsService;
use App\Service\ShipsService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * The 'My' Section reads from your cookie, so is custom and un-cacheable
 */
class ShowAction
{
    use UserTokenTrait;
    use GetShipTrait;

    const PER_PAGE = 100;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokenConfig $tokenConfig,
        UsersService $usersService,
        ShipsService $shipsService,
        ChannelsService $channelsService
    ): JsonResponse {

        $userId = $this->getUserId($request, $tokenConfig);
        if (!$userId) {
            throw new UnauthorizedHttpException('No user found');
        }
        $user = $usersService->getById($userId);
        if (!$user) {
            throw new UnauthorizedHttpException('No user found');
        }

        // get the ship. is it yours
        $ship = $this->getShipForOwnerId($request, $shipsService, $userId);
        if (!$ship) {
            throw new NotFoundHttpException('No such ship');
        }

        // get the ships location, and connecting routes (handled for the player)
        $shipWithLocation = $shipsService->getByIDWithLocation($ship->getId());
        if (!$shipWithLocation) {
            throw new NotFoundHttpException('No such ship');
        }
        $location = $shipWithLocation->getLocation();

        $data = [
            'ship' => $ship,
            'location' => $location,
        ];

        if ($location instanceof Port) {
            $data['directions'] = $this->getDirectionsFromPort($applicationConfig, $channelsService, $location, $user);
        }

        return new JsonResponse($data);
    }

    private function getDirectionsFromPort(
        ApplicationConfig $applicationConfig,
        ChannelsService $channelsService,
        Port $port,
        User $user
    ): array {
        // find all channels for a port, with their bearing and distance
        $channels = $channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        foreach ($channels as $channel) {
            /** @var $channel Channel */
            $bearing = $channel->getBearing()->getValue();
            $destination = $channel->getDestination();
            if (!$channel->getOrigin()->equals($port)) {
                $bearing = $channel->getBearing()->getOpposite();
                $destination = $channel->getOrigin();
            }

            $bearing = Bearing::getRotatedBearing((string) $bearing, $user->getRotationSteps());
            $directions[$bearing] = [
                'destination' => $destination,
                'distance' => $applicationConfig->getDistanceMultiplier() * $channel->getDistance(),
            ];
        }

        return [
            'type' => "Port",
            'directions' => $directions,
        ];
    }
}
