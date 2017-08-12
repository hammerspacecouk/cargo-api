<?php
declare(strict_types=1);

namespace App\Controller\Play\Ships;

use App\ApplicationTime;
use App\Config\ApplicationConfig;
use App\Config\TokenConfig;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Controller\Ships\Traits\GetShipTrait;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Service\ChannelsService;
use App\Service\ShipsService;
use App\Service\TokensService;
use App\Service\UsersService;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\UuidInterface;
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
        TokensService $tokensService,
        UsersService $usersService,
        ShipsService $shipsService,
        ChannelsService $channelsService
    ): JsonResponse
    {
        $userId = $this->getUserIdReadOnly($request, $tokenConfig, $tokensService);
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
                $channelsService,
                $location->getPort(),
                $ship,
                $user,
                $tokenConfig
            );
        }

        return new JsonResponse($data);
    }

    // todo - be less messy - share some properties
    private function getDirectionsFromPort(
        ApplicationConfig $applicationConfig,
        ChannelsService $channelsService,
        Port $port,
        Ship $ship,
        User $user,
        TokenConfig $tokenConfig
    ): array
    {
        // find all channels for a port, with their bearing and distance
        $channels = $channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        die('actions list to be created by actionsService');

        foreach ($channels as $channel) {
            /** @var $channel Channel */
            $bearing = $channel->getBearing()->getValue();
            $destination = $channel->getDestination();
            $reverseDirection = false;
            if (!$channel->getOrigin()->equals($port)) {
                $bearing = $channel->getBearing()->getOpposite();
                $destination = $channel->getOrigin();
                $reverseDirection = true;
            }

            $bearing = Bearing::getRotatedBearing((string)$bearing, $user->getRotationSteps());
            $directions[$bearing] = [
                'destination' => $destination,
                'distance' => $applicationConfig->getDistanceMultiplier() * $channel->getDistance(),
                'actionToken' => (string) $this->makeToken($ship, $channel, $reverseDirection, $tokenConfig)
            ];
        }

        return [
            'type' => "Port",
            'directions' => $directions,
        ];
    }

    private function makeToken(
        Ship $ship,
        Channel $channel,
        bool $reversed,
        TokenConfig $tokenConfig
    ): Token {
        // todo - abstract token building
        $signer = new Sha256();
        $token = (new Builder())->setIssuer($tokenConfig->getIssuer())
            ->setAudience($tokenConfig->getAudience())
            ->setId($tokenConfig->getId(), true)
            ->setIssuedAt(ApplicationTime::getTime()->getTimestamp())
            ->setExpiration(ApplicationTime::getTime()->add(new \DateInterval('P1D'))->getTimestamp())
            ->set('action', 'MOVE_SHIP_TO_CHANNEL') // todo - game actions constant
            ->set('shipUUID', (string) $ship->getId())
            ->set('channelUUID', (string) $channel->getId())
            ->set('isReversed', $reversed)
            ->sign($signer, $tokenConfig->getPrivateKey())
            ->getToken();

        return $token;
    }
}
