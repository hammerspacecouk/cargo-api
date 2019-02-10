<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Crate;
use App\Domain\Entity\CrateLocation;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Bearing;
use App\Domain\ValueObject\Direction;
use Ramsey\Uuid\UuidInterface;

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

        $latestShipCrateLocation = $this->cratesService->getMostRecentCrateLocationForShip($ship);
        $moveCrateKey = $latestShipCrateLocation ? $latestShipCrateLocation->toHash() : $ship->toHash();

        $shipTravelOptions = $this->effectsService->getShipTravelOptions($ship, $user);

        $port = $location->getPort();
        $data['port'] = $port;
        $data['travelOptions'] = $shipTravelOptions;
        $data['directions'] = $this->getDirectionsFromPort(
            $port,
            $ship,
            $user,
            $totalCrateValue,
            $location->getId(),
            );
        $data['shipsInLocation'] = $this->getShipsInPort($port, $ship);
        $data['events'] = $this->eventsService->findLatestForPort($port);

        $data['cratesInPort'] = $this->getCratesInPort($port, $ship, $user, \count($cratesOnShip), $moveCrateKey);
        $data['cratesOnShip'] = $this->getCratesOnShip($cratesOnShip, $port, $ship, $moveCrateKey);
        return $data;
    }

    private function getShipsInPort(
        Port $port,
        Ship $currentShip
    ): array {

        $ships = \array_map(function (ShipInPort $shipLocation) use ($currentShip, $port) {
            // apply any required changes or filter them out (set to null)
            $ship = $shipLocation->getShip();

            // remove the current ship from view
            if ($ship->getId()->equals($currentShip->getId())) {
                return null;
            }

            // get active effects for this ship
            $activeEffects = $this->effectsService->getActiveEffectsForShip($ship);
            foreach ($activeEffects as $effect) {
                /** @var Effect|Effect\DefenceEffect $effect */
                if (($effect instanceof Effect\DefenceEffect) && $effect->isInvisible()) {
                    return null;
                }
            }

            $offence = null;
            // make offence effects (if it's not your own ship)
            if (!$ship->getOwner()->equals($currentShip->getOwner())) {
                $offence = $this->effectsService->getOffenceOptionsAtShip(
                    $currentShip,
                    $ship,
                    $port
                );
            }

            return [
                'ship' => $ship,
                'offence' => $offence,
            ];

        }, $this->shipsService->findAllActiveInPort($port));

        // remove any nulls (filtered out)
        return \array_values(\array_filter($ships));
    }

    private function getCratesInPort(
        Port $port,
        Ship $ship,
        User $user,
        int $cratesAlreadyOnShip,
        string $moveCrateGroupKey
    ): array {
        $cratesInPort = $this->cratesService->findInPortForUser($port, $user);
        $canAddMoreCrates = $ship->getShipClass()->getCapacity() > $cratesAlreadyOnShip;

        return \array_map(function (CrateLocation $crateLocation) use (
            $ship,
            $port,
            $canAddMoreCrates,
            $moveCrateGroupKey
        ) {
            $crate = $crateLocation->getCrate();

            return [
                'token' => $canAddMoreCrates ? $this->cratesService->getPickupCrateToken(
                    $crate,
                    $ship,
                    $port,
                    $crateLocation->getId(),
                    $moveCrateGroupKey
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
        UuidInterface $currentLocation
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);

        $directions = Bearing::getEmptyBearingsList();
        $effectsToExpire = [];

        // get any active travel effects and send them into the algorithm service
        $activeEffect = $this->effectsService->getApplicableTravelEffectForShip($ship);
        /** @var Effect\TravelEffect|null $activeTravelEffect */
        $activeTravelEffect = null;
        if ($activeEffect) {
            $activeTravelEffect = $activeEffect->getEffect();
            $effectsToExpire[] = $activeEffect->getId();
        }

        foreach ($channels as $channel) {
            $bearing = Bearing::getRotatedBearing(
                $channel->getBearing($port)->getValue(),
                $user->getRotationSteps()
            );

            $journeyTimeSeconds = $this->algorithmService->getJourneyTime(
                $channel->getDistance(),
                $ship,
                $user->getRank(),
                $activeTravelEffect
            );

            $earnings = $this->algorithmService->getTotalEarnings(
                $totalCrateValue,
                $channel->getDistance(),
                $activeTravelEffect
            );

            $directionDetail = new Direction(
                $channel->getDestination($port),
                $channel,
                $user->getRank(),
                $ship,
                $journeyTimeSeconds,
                $earnings,
                );

            $token = null;
            if ($directionDetail->isAllowedToEnter()) {
                $token = $this->shipMovementService->getMoveShipToken(
                    $ship,
                    $channel,
                    $user,
                    $channel->isReversed($port),
                    $journeyTimeSeconds,
                    $earnings,
                    $currentLocation,
                    $effectsToExpire,
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
