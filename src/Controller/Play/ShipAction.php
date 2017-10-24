<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Domain\Entity\ShipInChannel;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Infrastructure\ApplicationConfig;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Service\ChannelsService;
use App\Service\ShipsService;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ShipAction
{
    use UserTokenTrait;
    use GetShipTrait;

    /** @var TokensService */
    private $tokensService;

    /** @var ChannelsService */
    private $channelsService;

    public function __invoke(
        Request $request,
        ApplicationConfig $applicationConfig,
        TokensService $tokensService,
        UsersService $usersService,
        ShipsService $shipsService,
        ChannelsService $channelsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);

        $this->tokensService = $tokensService;
        $this->channelsService = $channelsService;

        $userId = $this->getUserId($request, $this->tokensService);
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

        if ($location instanceof ShipInPort) {
            $data['directions'] = $this->getDirectionsFromPort(
                $applicationConfig,
                $location->getPort(),
                $ship,
                $user,
                $location
            );
        }

        return $this->userResponse(new JsonResponse($data));
    }

    // todo - be less messy - share some properties
    private function getDirectionsFromPort(
        ApplicationConfig $applicationConfig,
        Port $port,
        Ship $ship,
        User $user,
        ShipInPort $location
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        // the token key is based on the ship location, as this becomes invalid after use
        $groupTokenKey = (string) $location->getId();

        foreach ($channels as $channel) {
            $bearing = $channel->getBearing()->getValue();
            $destination = $channel->getDestination();
            $reverseDirection = false;
            if (!$channel->getOrigin()->equals($port)) {
                $bearing = $channel->getBearing()->getOpposite();
                $destination = $channel->getOrigin();
                $reverseDirection = true;
            }

            // todo - move this logic into a service
            $bearing = Bearing::getRotatedBearing((string)$bearing, $user->getRotationSteps());
            $journeyTimeMinutes = (int)round(
                ($applicationConfig->getDistanceMultiplier() *  $channel->getDistance() / 60)
            ) ;
            //* 60 * 60 todo - algorithm

            $token = $this->tokensService->getMoveShipToken(
                $ship,
                $channel,
                $reverseDirection,
                $journeyTimeMinutes,
                $groupTokenKey
            );

            $directions[$bearing] = [
                'destination' => $destination,
                'distanceUnit' => $channel->getDistance(),
                'journeyTimeMinutes' => $journeyTimeMinutes,
                'action' => $token
            ];
        }

        return [
            'type' => "Port",
            'actionPath' => MoveShipToken::getPath(),
            'directions' => $directions,
        ];
    }
}
