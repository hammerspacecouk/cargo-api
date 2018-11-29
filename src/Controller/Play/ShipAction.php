<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Domain\Entity\ShipInChannel;
use App\Controller\UserAuthenticationTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Response\ShipInChannelResponse;
use App\Response\ShipInPortResponse;
use App\Service\AuthenticationService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

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
    private $applicationConfig;
    private $shipInPortResponse;
    private $shipInChannelResponse;

    public static function getRouteDefinition(): array
    {
        return [
            static::class => new Route('/play/{uuid}', [
                '_controller' => self::class,
            ], [
                'uuid' => Uuid::VALID_PATTERN,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ShipsService $shipsService,
        ShipInChannelResponse $shipInChannelResponse,
        ShipInPortResponse $shipInPortResponse,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->shipsService = $shipsService;
        $this->logger = $logger;
        $this->shipInPortResponse = $shipInPortResponse;
        $this->shipInChannelResponse = $shipInChannelResponse;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(__CLASS__);
        $user = $this->getUser($request, $this->authenticationService);
        $ship = $this->getShipWithLocation($request, $user);
        $location = $ship->getLocation();

        if ($location instanceof ShipInPort) {
            $response = $this->shipInPortResponse->getResponseData(
                $user,
                $ship,
                $location
            );
        } elseif ($location instanceof ShipInChannel) {
            $response = $this->shipInChannelResponse->getResponseData(
                $user,
                $ship,
                $location
            );
        } else {
            throw new \RuntimeException('Unknown location');
        }
        return $this->userResponse(new JsonResponse($response), $this->authenticationService);
    }

    private function getShipWithLocation(
        Request $request,
        User $user
    ): Ship {
        $uuid = $this->getIDFromUrl($request);
        $ship = $this->shipsService->getByIDWithLocation($uuid);
        if (!$ship || !$ship->getOwner()->equals($user)) {
            throw new NotFoundHttpException('No such ship');
        }
        return $ship;
    }
}
