<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Domain\Entity\ShipInChannel;
use App\Infrastructure\ApplicationConfig;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Service\AuthenticationService;
use App\Service\ChannelsService;
use App\Service\ShipsService;
use App\Service\TokensService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ShipAction
{
    use UserTokenTrait;
    use GetShipTrait;

    private $applicationConfig;
    private $authenticationService;
    private $tokensService;
    private $shipsService;
    private $channelsService;
    private $usersService;
    private $logger;

    public function __construct(
        ApplicationConfig $applicationConfig,
        AuthenticationService $authenticationService,
        TokensService $tokensService,
        ShipsService $shipsService,
        UsersService $usersService,
        ChannelsService $channelsService,
        LoggerInterface $logger
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->authenticationService = $authenticationService;
        $this->tokensService = $tokensService;
        $this->shipsService = $shipsService;
        $this->channelsService = $channelsService;
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {

        $this->logger->debug(__CLASS__);

        $user = $this->getUser($request);
        if (!$user) {
            throw new UnauthorizedHttpException('No user found');
        }

        // get the ship. is it yours
        $ship = $this->getShipForOwnerId($request, $this->shipsService, $user->getId());
        if (!$ship) {
            throw new NotFoundHttpException('No such ship');
        }

        // get the ships location, and connecting routes (handled for the player)
        $shipWithLocation = $this->shipsService->getByIDWithLocation($ship->getId());
        if (!$shipWithLocation) {
            throw new NotFoundHttpException('No such ship');
        }
        $location = $shipWithLocation->getLocation();

        // get a ship "request-rename" token
        $requestShipNameToken = $this->tokensService->getRequestShipNameToken($user->getId(), $ship->getId());

        $data = [
            'ship' => $ship,
            'requestShipNameToken' => $requestShipNameToken,
            'status' => $shipWithLocation->getLocation()->getStatus(),
            'port' => null,
            'channel' => null,
            'directions' => null,
        ];

        if ($location instanceof ShipInPort) {
            $data['port'] = $location->getPort();
            $data['directions'] = $this->getDirectionsFromPort(
                $location->getPort(),
                $ship,
                $user,
                $location
            );
        }
        if ($location instanceof ShipInChannel) {
            $data['channel'] = $location;
        }

        return $this->userResponse(new JsonResponse($data));
    }

    private function getDirectionsFromPort(
        Port $port,
        Ship $ship,
        User $user,
        ShipInPort $location
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        // the token key is based on the ship location, so that all directions become invalid after use
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
                ($this->applicationConfig->getDistanceMultiplier() *  $channel->getDistance() / 60)
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

        return $directions;
    }
}
