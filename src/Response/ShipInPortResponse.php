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
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Arrays\filteredMap;

// todo - break up things to remove this sniff disable
// phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
class ShipInPortResponse extends AbstractShipInLocationResponse
{
    /**
     * @param User $user
     * @param Ship $ship
     * @param ShipLocation $location
     * @return array<string, mixed>
     */
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

        $cratesOnShip = $this->getCratesOnShip($cratesOnShip, $user, $port, $ship, $moveCrateKey);

        $allShipsInPort = $this->shipsService->findAllActiveInPort($port);
        $otherShips = $this->getOtherShipsInPort($allShipsInPort, $port, $ship, $data['tacticalOptions']);

        $blockadeStrength = null;
        $yourStrength = null;
        $blockadedBy = $location->getPort()->getBlockadedBy();
        if ($blockadedBy && $location->getPort()->isBlockaded()) {
            // calculate the blockade strength
            $blockadeStrength = $this->strengthForOwner($blockadedBy, $allShipsInPort);
            if (!$blockadedBy->equals($user)) {
                $yourStrength = $this->strengthForOwner($user, $allShipsInPort);
            }
        }

        $directions = [];
        if ($allowDirections) {
            $directions = $this->getDirectionsFromPort(
                $port,
                $ship,
                $user,
                (int)$totalCrateValue,
                $location->getId(),
                $blockadeStrength,
                $yourStrength
            );
        }

        $convoys = null;
        $leaveConvoyToken = $this->shipsService->getLeaveConvoyToken($ship);
        if ($allowOtherShips && !$leaveConvoyToken && $ship->canJoinConvoy()) {
            $convoys = $this->getConvoyOptions($ship, $allShipsInPort);
        }

        if (!$port->isSafe() && $user->getRank()->getThreshold() === 4) {
            $hasBlockedDirection = false;
            foreach ($directions as $direction) {
                if ($direction && $direction['action'] === null) {
                    $hasBlockedDirection = true;
                }
            }

            if ($hasBlockedDirection) {
                if (empty($convoys)) {
                    $tutorialStep = 5;
                } elseif (!$leaveConvoyToken) {
                    $tutorialStep = 6;
                }
            }
        }

        $sellToken = $this->shipsService->getSellToken($ship);

        $data['port'] = $port;
        $data['effectsToPurchase'] = $this->effectsService->getEffectsForLocation($ship, $user, $port);
        $data['tutorialStep'] = $tutorialStep;
        $data['directions'] = $directions;
        $data['shipsInLocation'] = $allowOtherShips ? $otherShips : [];
        $data['convoys'] = $convoys;
        $data['leaveConvoy'] = $leaveConvoyToken;
        $data['events'] = $this->eventsService->findLatestForPort($port);

        $data['cratesInPort'] = $cratesInPort;
        $data['cratesOnShip'] = $cratesOnShip;
        $data['sellToken'] = $sellToken;
        $data['blockadeStrength'] = $blockadeStrength;
        return $data;
    }

    /**
     * @param User $owner
     * @param Ship[] $allShips
     * @return int
     */
    private function strengthForOwner(User $owner, array $allShips): int
    {
        return array_reduce(
            $allShips,
            static function (int $carry, Ship $ship) use ($owner) {
                if ($ship->getOwner()->equals($owner)) {
                    return $carry + $ship->getStrength();
                }
                return $carry;
            },
            0
        );
    }

    /**
     * @param Ship[] $allShips
     * @param Port $port
     * @param Ship $currentShip
     * @param TacticalEffect[] $tacticalOptions
     * @return array<int, array<string, mixed>>
     */
    private function getOtherShipsInPort(
        array $allShips,
        Port $port,
        Ship $currentShip,
        array $tacticalOptions
    ): array {

        $ships = [];

        /** @var Ship[] $defendedPlayers */
        $defendedPlayers = [];

        foreach ($allShips as $ship) {
            // remove the current ship from view
            if ($ship->getId()->equals($currentShip->getId())) {
                continue;
            }

            // get active effects for this victim ship
            $activeEffects = $this->effectsService->getActiveEffectsForShip($ship);
            foreach ($activeEffects as $activeEffect) {
                $effect = $activeEffect->getEffect();
                if (($effect instanceof Effect\DefenceEffect)
                    && $effect->isInvisible()
                    && !$ship->getOwner()->equals($currentShip->getOwner())
                ) {
                    // don't include invisible ships in the list
                    continue 2;
                }
            }

            $ownerID = $ship->getOwner()->getId();
            if ($ship->getShipClass()->isDefence()) {
                $defendedPlayers[(string)$ownerID] = $ship;
            }

            $offence = null;
            $isVulnerable = $ship->getShipClass()->isDefence() || !isset($defendedPlayers[(string)$ownerID]);
            $inactiveReason = null;
            // make offence effects if all of the following are satisfied:
            // - it's not your own ship
            // - the current ship is not a probe
            // - the current port is not a safe haven
            // - a vulnerable ship without a nearby defence ship
            if (!$port->isSafe() &&
                !$currentShip->getShipClass()->isProbe() &&
                !$ship->getOwner()->equals($currentShip->getOwner())
            ) {
                if ($isVulnerable) {
                    $offence = $this->effectsService->getOffenceOptionsAtShip(
                        $currentShip,
                        $ship,
                        $port,
                        $tacticalOptions,
                        $currentShip->getOwner()->getMarket()->getMilitaryMultiplier()
                    );
                } else {
                    $inactiveReason = 'Defended by ' . $defendedPlayers[(string)$ownerID]->getName();
                }
            }

            $ships[] = [
                'ship' => $ship,
                'offence' => $offence,
                'inactiveReason' => $inactiveReason,
            ];
        }

        return $ships;
    }

    /**
     * @param Port $port
     * @param Ship $ship
     * @param User $user
     * @param int $cratesAlreadyOnShip
     * @param string $moveCrateGroupKey
     * @return array<string, mixed>
     */
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
            $user,
            $ship,
            $port,
            $canAddMoreCrates,
            $moveCrateGroupKey
        ) {
            $crate = $crateLocation->getCrate();

            return [
                'token' => $canAddMoreCrates ? $this->cratesService->getPickupCrateToken(
                    $user,
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

    /**
     * @param Crate[] $crates
     * @param User $user
     * @param Port $port
     * @param Ship $ship
     * @param string|null $groupTokenKey
     * @return array<string, mixed>
     */
    private function getCratesOnShip(
        array $crates,
        User $user,
        Port $port,
        Ship $ship,
        ?string $groupTokenKey
    ): array {
        return \array_map(function (Crate $crate) use ($user, $ship, $port, $groupTokenKey) {
            $token = null;
            if ($groupTokenKey) {
                $token = $this->cratesService->getDropCrateToken($user, $crate, $ship, $port, $groupTokenKey);
            }
            return [
                'token' => $token,
                'crate' => $crate,
                'valuePerLY' => $crate->getValuePerLightYear($this->applicationConfig->getDistanceMultiplier()),
            ];
        }, $crates);
    }

    /**
     * @param Port $port
     * @param Ship $ship
     * @param User $user
     * @param int $totalCrateValue
     * @param UuidInterface $currentLocation
     * @param int|null $blockadeStrength
     * @param int|null $yourStrength - null if it is your blockade
     * @return array<string, mixed>
     */
    private function getDirectionsFromPort(
        Port $port,
        Ship $ship,
        User $user,
        int $totalCrateValue,
        UuidInterface $currentLocation,
        ?int $blockadeStrength,
        ?int $yourStrength
    ): array {

        // find all channels for a port, with their bearing and distance
        $channels = $this->channelsService->getAllLinkedToPort($port);
        $homePort = $this->portsService->findHomePortForUserId($user->getId());

        $directions = Bearing::getEmptyBearingsList();

        $convoyShips = [$ship];
        if ($ship->getConvoyId()) {
            $convoyShips = $this->shipsService->findAllInConvoy($ship->getConvoyId());
        }

        $activeTravelEffects = [];
        foreach ($convoyShips as $convoyShip) {
            $shipEffects = $this->effectsService->getActiveTravelEffectsForShip($convoyShip);
            foreach ($shipEffects as $shipEffect) {
                $activeTravelEffects[] = $shipEffect;
            }
        }

        foreach ($channels as $channel) {
            $bearing = Bearing::getRotatedBearing(
                $channel->getBearing($port)->getValue(),
                $user->getRotationSteps()
            );

            $slowestJourneyTimeSeconds = 0;
            foreach ($convoyShips as $convoyShip) {
                // combine all the travel effects

                $journeyTimeSeconds = $this->algorithmService->getJourneyTime(
                    $channel->getDistance(),
                    $convoyShip,
                    $user,
                    $activeTravelEffects
                );
                if (!$slowestJourneyTimeSeconds || ($journeyTimeSeconds > $slowestJourneyTimeSeconds)) {
                    $slowestJourneyTimeSeconds = $journeyTimeSeconds;
                }
            }

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
                $slowestJourneyTimeSeconds,
                $earnings,
                $this->shipLocationsService->getLatestVisitTimeForPort($user, $destination),
                $convoyShips,
                $blockadeStrength,
                $yourStrength,
            );

            $token = null;
            if ($directionDetail->isAllowedToEnter()) {
                $breakingBlockade = $user->getRank()->isAffectedByBlockades() &&
                    $yourStrength &&
                    $yourStrength >= $blockadeStrength;
                $token = $this->shipMovementService->getMoveShipToken(
                    $ship,
                    $channel,
                    $user,
                    $channel->isReversed($port),
                    $slowestJourneyTimeSeconds,
                    $earnings,
                    $currentLocation,
                    $activeTravelEffects,
                    $breakingBlockade
                );
            }

            $directions[$bearing] = [
                'action' => $token,
                'detail' => $directionDetail,
            ];
        }

        return $directions;
    }

    /**
     * @param Ship $currentShip
     * @param Ship[] $allShips
     * @return array
     */
    private function getConvoyOptions(Ship $currentShip, array $allShips): array
    {
        if ($currentShip->isInConvoy()) {
            return [];
        }

        $me = $currentShip->getOwner();
        /** @var Ship[] $myShips */
        $myShips = filteredMap($allShips, static function (Ship $shipData) use ($me, $currentShip) {
            if (!$shipData->equals($currentShip) && $shipData->canJoinConvoy() && $me->equals($shipData->getOwner())) {
                return $shipData;
            }
            return null;
        });
        if (empty($myShips)) {
            return [];
        }

        $convoys = [];
        foreach ($myShips as $myShip) {
            $convoyId = (string)($myShip->getConvoyId() ?: Uuid::uuid6());
            if (!isset($convoys[$convoyId])) {
                $convoys[$convoyId] = [
                    'token' => $this->shipsService->getConvoyToken($currentShip, $myShip),
                    'ships' => [],
                ];
            }
            $convoys[$convoyId]['ships'][] = $myShip;
        }
        return array_values($convoys);
    }
}
