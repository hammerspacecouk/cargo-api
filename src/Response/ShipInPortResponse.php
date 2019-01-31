<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Crate;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\Direction;
use Ramsey\Uuid\Uuid;

class ShipInPortResponse extends AbstractShipInLocationResponse
{
    public function getResponseData(
        User $user,
        Ship $ship,
        ShipLocation $location
    ): array {
        if (!$location instanceof ShipInPort) {
            throw new \InvalidArgumentException('Location is not in a port');
        }
        $data = $this->getBaseData($user, $ship, $location);

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
            $totalCrateValue,
            $groupTokenKey,
            );
        $data['shipsInLocation'] = $this->getShipsInPort($port, $ship);
        $data['events'] = $this->eventsService->findLatestForPort($port);

        $data['cratesInPort'] = $this->getCratesInPort($port, $ship, $user, \count($cratesOnShip), $groupTokenKey);
        $data['cratesOnShip'] = $this->getCratesOnShip($cratesOnShip, $port, $ship, $groupTokenKey);
        return $data;
    }

    private function getShipsInPort(
        Port $port,
        Ship $currentShip
    ): array {
        $ships = \array_map(function(Ship $ship) use ($currentShip) {
            // apply any required changes or filter them out (set to null)

            // remove the current ship from view
            if ($ship->getId()->equals($currentShip->getId())) {
                return null;
            }

            // get active effects for this ship
            $activeEffects = $this->effectsService->getActiveEffectsForShip($ship);
            foreach ($activeEffects as $effect) {
                /** @var Effect $effect */
                if (($effect instanceof Effect\DefenceEffect) && $effect->isInvisible()) {
                    return null;
                }
            }

            return $ship;

        }, $this->shipsService->findAllActiveInPort($port));

        // remove any nulls (filtered out)
        return \array_values(\array_filter($ships));
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
        int $totalCrateValue,
        string $groupTokenKey
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();

        foreach ($channels as $channel) {
            $bearing = Bearing::getRotatedBearing(
                $channel->getBearing($port)->getValue(),
                $user->getRotationSteps()
            );

            $journeyTimeSeconds = $this->algorithmService->getJourneyTime(
                $channel->getDistance(),
                $ship,
                $user->getRank()
            );

            $directionDetail = new Direction(
                $channel->getDestination($port),
                $channel,
                $user->getRank(),
                $ship,
                $journeyTimeSeconds,
                $this->algorithmService->getTotalEarnings($totalCrateValue, $channel->getDistance()),
                );

            $token = null;
            if ($directionDetail->isAllowedToEnter()) {
                $token = $this->shipMovementService->getMoveShipToken(
                    $ship,
                    $channel,
                    $user,
                    $channel->isReversed($port),
                    $journeyTimeSeconds,
                    $groupTokenKey
                );
            }

            $directions[$bearing] = [
                'action' => $token,
                'detail' => $directionDetail,
            ];
        }

        return $directions;
    }
}
