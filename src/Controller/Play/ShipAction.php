<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Domain\Entity\Crate;
use App\Domain\Entity\ShipInChannel;
use App\Domain\ValueObject\PlayerRankStatus;
use App\Controller\UserAuthenticationTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Service\AlgorithmService;
use App\Service\AuthenticationService;
use App\Service\ChannelsService;
use App\Service\CratesService;
use App\Service\EventsService;
use App\Service\PlayerRanksService;
use App\Service\Ships\ShipMovementService;
use App\Service\Ships\ShipNameService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShipAction
{
    use UserAuthenticationTrait;
    use GetShipTrait;

    private $algorithmService;
    private $authenticationService;
    private $shipsService;
    private $shipMovementService;
    private $shipNameService;
    private $channelsService;
    private $logger;
    private $playerRanksService;
    private $eventsService;
    private $cratesService;

    public function __construct(
        AlgorithmService $algorithmService,
        AuthenticationService $authenticationService,
        CratesService $cratesService,
        EventsService $eventsService,
        PlayerRanksService $playerRanksService,
        ShipsService $shipsService,
        ShipMovementService $shipMovementService,
        ShipNameService $shipNameService,
        ChannelsService $channelsService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->shipMovementService = $shipMovementService;
        $this->shipNameService = $shipNameService;
        $this->channelsService = $channelsService;
        $this->logger = $logger;
        $this->playerRanksService = $playerRanksService;
        $this->eventsService = $eventsService;
        $this->algorithmService = $algorithmService;
        $this->cratesService = $cratesService;
    }

    public function __invoke(
        Request $request
    ): Response {

        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);

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
        $requestShipNameTransaction = $this->shipNameService->getRequestShipNameTransaction(
            $user->getId(),
            $ship->getId()
        );

        $data = [
            'ship' => $ship,
            'requestShipName' => $requestShipNameTransaction, // todo - move to fleet API
            'status' => $shipWithLocation->getLocation()->getStatus(),
            'port' => null,
            'channel' => null,
            'directions' => null,
            'events' => [],
            'cratesOnShip' => $this->cratesService->findForShip($ship),
        ];

        $rankStatus = $this->playerRanksService->getForUser($user);

        if ($location instanceof ShipInPort) {
            $port = $location->getPort();
            $data['port'] = $port;
            $data['directions'] = $this->getDirectionsFromPort(
                $port,
                $ship,
                $user,
                $location,
                $rankStatus
            );
            $data['shipsInLocation'] = $this->shipsService->findAllActiveInPort($port);
            $data['events'] = $this->eventsService->findLatestForPort($port);
            $cratesInPort = $this->cratesService->findInPortForUser($port, $user);

            $data['cratesInPort'] = \array_map(function(Crate $crate) {
                return [
                    'token' => 'ttt', // todo
                    'crate' => $crate,
                ];
            }, $cratesInPort);
        }
        if ($location instanceof ShipInChannel) {
            $data['channel'] = $location;
            // todo - other players in this channel to show you passing them
        }

        $data['playerScore'] = $user->getScore();
        $data['playerRankStatus'] = $rankStatus;

        return $this->userResponse(new JsonResponse($data), $this->authenticationService);
    }

    private function getDirectionsFromPort(
        Port $port,
        Ship $ship,
        User $user,
        ShipInPort $location,
        PlayerRankStatus $rankStatus
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        // the token key is based on the ship location, so that all directions become invalid after use
        $groupTokenKey = (string)$location->getId();

        foreach ($channels as $channel) {
            $bearing = $channel->getBearing()->getValue();
            $destination = $channel->getDestination();
            $reverseDirection = false;
            if (!$channel->getOrigin()->equals($port)) {
                $bearing = $channel->getBearing()->getOpposite();
                $destination = $channel->getOrigin();
                $reverseDirection = true;
            }

            $bearing = Bearing::getRotatedBearing((string)$bearing, $user->getRotationSteps());

            $journeyTimeSeconds = $this->algorithmService->getJourneyTime(
                $channel->getDistance(),
                $ship,
                $rankStatus
            );

            $minimumRank = $channel->getMinimumRank();
            $meetsRequiredRank = $minimumRank ? $rankStatus->getCurrentRank()->meets($minimumRank) : true;
            $meetsMinimumStrength = $ship->meetsStrength($channel->getMinimumStrength());

            $token = null;

            if ($meetsRequiredRank && $meetsMinimumStrength) {
                $token = $this->shipMovementService->getMoveShipToken(
                    $ship,
                    $channel,
                    $user,
                    $reverseDirection,
                    $journeyTimeSeconds,
                    $groupTokenKey
                );
            }

            $directions[$bearing] = [
                'destination' => $destination,
                'distanceUnit' => $channel->getDistance(),
                'journeyTimeSeconds' => $journeyTimeSeconds,
                'action' => $token,
                'minimumRank' => !$meetsRequiredRank ? $minimumRank : null,
                'minimumStrength' => !$meetsMinimumStrength ? $channel->getMinimumStrength() : null,
            ];
        }

        return $directions;
    }
}
