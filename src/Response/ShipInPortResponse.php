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
use App\Domain\ValueObject\TacticalEffect;
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

        $crateLocationsOnShip = $this->cratesService->findForShip($ship);

        // find the total value and generate a key based on the current crates on the ship,
        // so an action can only be done once. Then break out the crates from the location
        $cratesOnShip = [];
        $totalCrateValue = 0;
        $moveCrateKey = '';

        foreach ($crateLocationsOnShip as $crateLocation) {
            $crate = $crateLocation->getCrate();
            $moveCrateKey .= $crateLocation->toHash();

            $totalCrateValue += $crate->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier());
            $cratesOnShip[] = $crate;
        }

        // if there are no crates the moveCrateKey will end up with a previous key, so we'll find a key from the most
        // recent location, even if it's no longer current.
        if (empty($moveCrateKey)) {
            $latestShipCrateLocation = $this->cratesService->getMostRecentCrateLocationForShip($ship);
            $moveCrateKey = $latestShipCrateLocation ? $latestShipCrateLocation->toHash() : $ship->toHash();
        }

        $port = $location->getPort();
        $cratesInPort = $this->getCratesInPort($port, $ship, $user, \count($cratesOnShip), $moveCrateKey);

        $tutorialStep = null;
        $allowDirections = true;
        $allowOtherShips = true;
        if ($user->getRank()->isTutorial()) {
            $allowOtherShips = false;
            $data['tacticalOptions'] = [];
            // tutorial will have only received the reserved crate
            $hasPickedUpCrate = empty($cratesInPort);
            if ($hasPickedUpCrate) {
                $allowDirections = true;
                $moveCrateKey = null; // prevents it being put back down
                $tutorialStep = 2;
            } else {
                $allowDirections = false;
                $tutorialStep = 1;
            }
        }
        if ($user->getRank()->getThreshold() === 2) {
            $tutorialStep = 3;
        }
        if (!$port->isSafe() && $user->getRank()->getThreshold() === 3) {
            $tutorialStep = 4;
        }

        $cratesOnShip = $this->getCratesOnShip($cratesOnShip, $port, $ship, $moveCrateKey);
        $directions = [];
        if ($allowDirections) {
            $directions = $this->getDirectionsFromPort(
                $port,
                $ship,
                $user,
                (int)$totalCrateValue,
                $location->getId(),
                $data['tacticalOptions'],
            );
        }
        $otherShips = [];
        if ($allowOtherShips) {
            $otherShips = $this->getShipsInPort($port, $ship, $data['tacticalOptions']);
        }

        $data['port'] = $port;
        $data['effectsToPurchase'] = $this->effectsService->getEffectsForLocation($ship, $user, $port);
        $data['tutorialStep'] = $tutorialStep;
        $data['directions'] = $directions;
        $data['shipsInLocation'] = $otherShips;
        $data['events'] = $this->eventsService->findLatestForPort($port);

        $data['cratesInPort'] = $cratesInPort;
        $data['cratesOnShip'] = $cratesOnShip;
        return $data;
    }

    private function getShipsInPort(
        Port $port,
        Ship $currentShip,
        array $tacticalOptions
    ): array {

        $ships = [];

        foreach ($this->shipsService->findAllActiveInPort($port) as $ship) {
            // remove the current ship from view
            if ($ship->getId()->equals($currentShip->getId())) {
                continue;
            }
            // get active effects for this victim ship
            $activeEffects = $this->effectsService->getActiveEffectsForShip($ship);
            foreach ($activeEffects as $activeEffect) {
                $effect = $activeEffect->getEffect();
                if (($effect instanceof Effect\DefenceEffect) && $effect->isInvisible()) {
                    // don't include invisible ships in the list
                    continue 2;
                }
            }

            $offence = null;
            // make offence effects if all of the following are satisfied:
            // - it's not your own ship
            // - the current ship is not a probe
            // - the current port is not a safe haven
            if (!$port->isSafe() &&
                !$currentShip->getShipClass()->isProbe() &&
                !$ship->getOwner()->equals($currentShip->getOwner())
            ) {
                $offence = $this->effectsService->getOffenceOptionsAtShip(
                    $currentShip,
                    $ship,
                    $port,
                    $tacticalOptions
                );
            }

            $ships[] = [
                'ship' => $ship,
                'offence' => $offence,
            ];
        }

        return $ships;
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
        ?string $groupTokenKey
    ): array {
        return \array_map(function (Crate $crate) use ($ship, $port, $groupTokenKey) {
            $token = null;
            if ($groupTokenKey) {
                $token = $this->cratesService->getDropCrateToken($crate, $ship, $port, $groupTokenKey);
            }
            return [
                'token' => $token,
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
        UuidInterface $currentLocation,
        array $tacticalOptions
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);
        $homePort = $this->portsService->findHomePortForUserId($user->getId());

        $directions = Bearing::getEmptyBearingsList();

        $activeTravelEffects = array_filter($tacticalOptions, static function (TacticalEffect $tacticalEffect) {
            return $tacticalEffect->getEffect() instanceof Effect\TravelEffect && $tacticalEffect->isActive();
        });

        foreach ($channels as $channel) {
            $bearing = Bearing::getRotatedBearing(
                $channel->getBearing($port)->getValue(),
                $user->getRotationSteps()
            );

            $journeyTimeSeconds = $this->algorithmService->getJourneyTime(
                $channel->getDistance(),
                $ship,
                $user->getRank(),
                $activeTravelEffects
            );

            $earnings = $this->algorithmService->getTotalEarnings(
                $totalCrateValue,
                $channel->getDistance(),
                $activeTravelEffects
            );

            $destination = $channel->getDestination($port);
            $directionDetail = new Direction(
                $destination,
                $channel,
                $user->getRank(),
                $ship,
                $destination->equals($homePort),
                $journeyTimeSeconds,
                $earnings,
                $this->shipLocationsService->getLatestVisitTimeForPort($user, $destination)
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
                    $activeTravelEffects,
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
