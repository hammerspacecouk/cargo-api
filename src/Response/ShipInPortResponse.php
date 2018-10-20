<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Crate;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\PlayerRankStatus;
use Ramsey\Uuid\Uuid;

class ShipInPortResponse extends AbstractShipInLocationResponse
{
    public function getResponseDataForLocation(
        array $data,
        User $user,
        Ship $ship,
        ShipLocation $location,
        PlayerRankStatus $rankStatus
    ): array {
        if (!$location instanceof ShipInPort) {
            throw new \InvalidArgumentException('Location is not in a port');
        }

        $cratesOnShip = $this->cratesService->findForShip($ship);
        $totalCrateValue = \array_reduce($cratesOnShip, function (int $acc, Crate $crate) {
            return $acc + $crate->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier());
        }, 0);

        // all buttons get the same key. This is so that they are all invalidated as soon as one is used.
        $groupTokenKey = (string)Uuid::uuid4();

        $port = $location->getPort();
        $data['port'] = $port;
        $data['directions'] = $this->getDirectionsFromPort(
            $port,
            $ship,
            $user,
            $location,
            $rankStatus,
            $totalCrateValue,
            $groupTokenKey
        );
        $data['shipsInLocation'] = $this->shipsService->findAllActiveInPort($port);
        $data['events'] = $this->eventsService->findLatestForPort($port);

        $data['cratesInPort'] = $this->getCratesInPort($port, $ship, $user, \count($cratesOnShip), $groupTokenKey);
        $data['cratesOnShip'] = $this->getCratesOnShip($cratesOnShip, $port, $ship, $groupTokenKey);
        return $data;
    }

    private function getCratesInPort(
        Port $port,
        Ship $ship,
        User $user,
        int $cratesAlreadyOnShip,
        string $groupTokenKey
    ): array {
        $cratesInPort = $this->cratesService->findInPortForUser($port, $user);
        $canAddMoreCrates = $ship->getShipClass()->getCapacity() > $cratesAlreadyOnShip;

        return \array_map(function (Crate $crate) use ($ship, $port, $groupTokenKey, $canAddMoreCrates) {
            return [
                'token' => $canAddMoreCrates ? $this->cratesService->getPickupCrateToken(
                    $crate,
                    $ship,
                    $port,
                    $groupTokenKey
                ) : null,
                'crate' => $crate,
                'valuePerLY' => $crate->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier()),
            ];
        }, $cratesInPort);
    }

    private function getCratesOnShip(
        array $crates,
        Port $port,
        Ship $ship,
        string $groupTokenKey
    ): array {

        return \array_map(function (Crate $crate) use ($ship, $port, $groupTokenKey) {
            return [
                'token' => $this->cratesService->getDropCrateToken($crate, $ship, $port, $groupTokenKey),
                'crate' => $crate,
                'valuePerLY' => $crate->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier()),
            ];
        }, $crates);
    }

    private function getDirectionsFromPort(
        Port $port,
        Ship $ship,
        User $user,
        ShipInPort $location,
        PlayerRankStatus $rankStatus,
        int $totalCrateValue,
        string $groupTokenKey
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

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
                'earnings' => $this->algorithmService->getTotalEarnings($totalCrateValue, $channel->getDistance()),
                'journeyTimeSeconds' => $journeyTimeSeconds,
                'action' => $token,
                'minimumRank' => !$meetsRequiredRank ? $minimumRank : null,
                'minimumStrength' => !$meetsMinimumStrength ? $channel->getMinimumStrength() : null,
            ];
        }

        return $directions;
    }
}
