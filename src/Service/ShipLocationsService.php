<?php
declare(strict_types=1);

namespace App\Service;

use _HumbugBox01d8f9a04075\Symfony\Component\Console\Exception\LogicException;
use App\Data\Database\Entity\CrateLocation;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\Entity\Ship as DbShip;
use App\Data\Database\Entity\ShipLocation as DbShipLocation;
use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Dates\intervalToSeconds;
use function array_map;

class ShipLocationsService extends AbstractService
{
    private const AUTO_MOVE_TIME = 'PT1H';

    public function processOldestExpired(
        DateTimeImmutable $since,
        int $limit
    ): int {
        $locations = $this->entityManager->getShipLocationRepo()->getOldestExpired(
            $since,
            $limit,
            Query::HYDRATE_OBJECT,
        );
        $total = count($locations);

        $this->logger->info('Processing ' . $total . ' arrivals in this batch');
        if (!empty($locations)) {
            foreach ($locations as $location) {
                $this->moveShipFromChannelToPort($location);
            }
        }
        return $total;
    }

    public function getRecentForShip(Ship $ship): array
    {
        $locations = $this->entityManager->getShipLocationRepo()->getRecentForShipID(
            $ship->getId(),
            6
        );
        return $this->mapMany($locations);
    }

    public function getCurrentForShip(Ship $ship): ShipLocation
    {
        $result = $this->entityManager->getShipLocationRepo()->getCurrentForShipId($ship->getId());
        $mapper = $this->mapperFactory->createShipLocationMapper();

        return $mapper->getShipLocation($result);
    }

    /**
     * @param DateTimeImmutable $before
     * @param int $limit
     * @return ShipLocation[]
     */
    public function getStagnantProbes(
        DateTimeImmutable $before,
        int $limit
    ): array {
        $results = $this->entityManager->getShipLocationRepo()
            ->getProbesThatArrivedInPortBeforeTime(
                $before->sub(new DateInterval(self::AUTO_MOVE_TIME)),
                $limit
            );
        return $this->mapMany($results);
    }

    public function getLatestVisitTimeForPort(User $user, Port $port): ?DateTimeImmutable
    {
        $visit = $this->entityManager->getPortVisitRepo()->getForPortAndUser(
            $port->getId(),
            $user->getId(),
        );
        if ($visit) {
            return $visit['lastVisited'];
        }
        return null;
    }

    private function moveShipFromChannelToPort(DbShipLocation $currentLocation): void
    {
        $ship = $currentLocation->ship;
        $destinationPort = $currentLocation->getDestination();
        $channel = $currentLocation->channel;
        if (!$channel) {
            throw new LogicException('Tried to move from a channel but did not have a channel object');
        }

        $usersRepo = $this->entityManager->getUserRepo();
        $portVisitRepo = $this->entityManager->getPortVisitRepo();

        $ownerId = $ship->owner->id;
        /** @var DbUser $owner */
        $owner = $usersRepo->getByID($ownerId, Query::HYDRATE_OBJECT);
        $portVisit = $portVisitRepo->getForPortAndUser(
            $destinationPort->id,
            $ownerId,
            Query::HYDRATE_OBJECT,
        );
        $isFirstJourney = null;
        // if this was their first travel from the home (visits = 1) we're going to make a new crate
        if (!$portVisit && $portVisitRepo->countForPlayerId($ownerId) === 1) {
            $isFirstJourney = true;
        }

        // reverse the delta from this journey originally
        $delta = (int)-$currentLocation->scoreDelta;

        $crateLocations = $this->entityManager->getCrateLocationRepo()->findCurrentForShipID(
            $ship->id,
            Query::HYDRATE_OBJECT,
        );

        /** @var DbShipLocation[] $shipsInPort */
        $shipsInPort = $this->entityManager->getShipLocationRepo()->getActiveShipsForPortId(
            $destinationPort->id,
            Query::HYDRATE_OBJECT
        );

        $centiDistance = max(1, $channel->distance * 100);

        $this->entityManager->transactional(function () use (
            $currentLocation,
            $ship,
            $destinationPort,
            $portVisit,
            $owner,
            $crateLocations,
            $delta,
            $isFirstJourney,
            $shipsInPort,
            $centiDistance
        ) {
            // remove the old ship location
            $currentLocation->isCurrent = false;
            $this->entityManager->persist($currentLocation);

            $this->entityManager->getShipLocationRepo()->makeInPort($ship, $destinationPort);

            // add this port to the list of visited ports for this user
            $this->entityManager->getPortVisitRepo()->recordVisit($portVisit, $owner, $destinationPort);
            $this->entityManager->getUserAchievementRepo()->recordSpecialVisit($owner->id, $destinationPort->id);

            // move all the crates to the port
            foreach ($crateLocations as $crateLocation) {
                /** @var CrateLocation $crateLocation */
                $crateLocation->isCurrent = false;
                $this->entityManager->getCrateLocationRepo()->exitLocation($crateLocation->crate);
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crateLocation->crate,
                    $destinationPort
                );
                $this->entityManager->getCrateRepo()->removeReservation($crateLocation->crate);

                if ($crateLocation->crate->isGoal && $destinationPort->isDestination) {
                    // winner
                    $this->entityManager->getUserAchievementRepo()->recordWin($owner->id);
                    $this->entityManager->getUserRepo()->recordWinner($owner);
                }
            }

            if ($isFirstJourney) {
                $crate = $this->entityManager->getCrateRepo()->newRandomCrate();
                $this->entityManager->getCrateLocationRepo()->makeInPort(
                    $crate,
                    $destinationPort
                );
                $this->entityManager->getUserAchievementRepo()->recordFirstTravel($owner->id);
            }

            // update the user's total travel distance and record achievements
            $owner->centiDistanceTravelled += $centiDistance;




            ///// TODO - ACHIEVEMENTS





            // update the users score
            $this->entityManager->getUserRepo()->updateScoreRate($owner, $delta);

            $timeInChannel = $currentLocation->entryTime->diff(DateTimeFactory::now());
            if (intervalToSeconds($timeInChannel) >= 60 * 60 * 24) {
                $this->entityManager->getUserAchievementRepo()->recordLongTravel($owner->id);
            }

            if (!$destinationPort->isSafeHaven) {
                $this->entityManager->getUserAchievementRepo()->recordArrivalToUnsafeTerritory($owner->id);
            }
            $this->handlePlague(
                $destinationPort,
                $ship,
                $shipsInPort,
                $owner,
            );
        });

        // as a safety check if some race condition happened, confirm the user delta
        $expectedDelta = $this->entityManager->getShipLocationRepo()->sumDeltaForUserId($ownerId);
        $owner->scoreRate = $expectedDelta;
        $this->entityManager->persist($owner);
        $this->entityManager->flush();
    }

    private function handlePlague(
        DbPort $destinationPort,
        DbShip $ship,
        array $shipsInPort,
        DbUser $owner
    ): void {
        shuffle($shipsInPort);

        $ownersWithHospitalShip = [];
        $infectedShip = null;
        foreach ($shipsInPort as $shipInPort) {
            if ($shipInPort->ship->hasPlague) {
                // are there any infected ships already here that will infect you?
                $infectedShip = $shipInPort->ship;
            }
            if ($shipInPort->ship->shipClass->isHospitalShip) {
                // does anybody have any hospital ships here?
                $ownersWithHospitalShip[$shipInPort->ship->owner->id->toString()] = true;
            }
        }

        if (!$destinationPort->isSafeHaven) {
            if ($ship->hasPlague) {
                $this->infectShips($shipsInPort, $ship, $destinationPort);
            } elseif ($infectedShip &&
                !$ship->shipClass->isHospitalShip &&
                !($ownersWithHospitalShip[$owner->id->toString()] ?? false)
            ) {
                $this->exposedToInfection($ship, $infectedShip, $destinationPort);
            }
        }

        $isCured = false;
        if ($ship->shipClass->isHospitalShip) {
            $isCured = $this->cureOnArrival($shipsInPort, $owner, $destinationPort);
        } elseif ($ship->hasPlague && ($ownersWithHospitalShip[$owner->id->toString()] ?? false)) {
            // if there is a hospital ship here, cure this ship
            $ship->hasPlague = false;
            $this->entityManager->persist($ship);
            $this->entityManager->getEventRepo()->logCured(
                $ship,
                $destinationPort
            );
            $isCured = true;
        }
        if ($isCured) {
            $this->entityManager->getUserAchievementRepo()->recordCured($owner->id);
        }
    }

    private function cureOnArrival(array $shipsInPort, DbUser $owner, DbPort $destinationPort): bool
    {
        $isCured = false;
        // if this ship is a hospital ship, cure other ships of yours here
        foreach ($shipsInPort as $shipInPort) {
            if (!$shipInPort->ship->hasPlague ||
                !$shipInPort->ship->owner->id->equals($owner->id)
            ) {
                continue;
            }
            $shipInPort->ship->hasPlague = false;
            $this->entityManager->persist($shipInPort->ship);
            $this->entityManager->getEventRepo()->logCured(
                $shipInPort->ship,
                $destinationPort
            );
            $isCured = true;
        }
        return $isCured;
    }

    private function exposedToInfection(DbShip $ship, DbShip $infectedShip, DbPort $destinationPort): void
    {
        // get infected if you don't have a hospital ship and you're not immune
        $activeEffects = $this->getActiveEffectsForShipId($ship->id);
        $isImmune = false;
        foreach ($activeEffects as $activeEffect) {
            $effect = $activeEffect->getEffect();
            if (($effect instanceof Effect\DefenceEffect) && $effect->isImmuneToPlague()) {
                $isImmune = true;
            }
        }

        if (!$isImmune) {
            $ship->hasPlague = true;
            $this->entityManager->persist($ship);
            $this->entityManager->getEventRepo()->logInfection(
                $infectedShip,
                $ship,
                $destinationPort
            );
            $this->entityManager->getUserAchievementRepo()->recordContactWithInfected($ship->owner->id);
        }
    }

    private function infectShips(array $shipsInPort, DbShip $ship, DbPort $destinationPort): void
    {
        // infect all ships that don't have a hospital ship
        foreach ($shipsInPort as $shipInPort) {
            // probes can't catch it, and it can't be caught if the player has a hospital ship here
            if (!$shipInPort->ship->hasPlague &&
                !$shipInPort->ship->shipClass->autoNavigate &&
                !($ownersWithHospitalShip[$shipInPort->ship->owner->id->toString()] ?? false)
            ) {
                $this->attemptToInfectShip($shipInPort, $ship, $destinationPort);
            }
        }
    }

    private function attemptToInfectShip(DbShipLocation $shipInPort, DbShip $ship, DbPort $destinationPort): void
    {
        $activeEffects = $this->getActiveEffectsForShipId($shipInPort->ship->id);
        $isImmune = false;
        foreach ($activeEffects as $activeEffect) {
            $effect = $activeEffect->getEffect();
            if (($effect instanceof Effect\DefenceEffect) && $effect->isImmuneToPlague()) {
                $isImmune = true;
            }
        }
        if (!$isImmune) {
            $shipInPort->ship->hasPlague = true;
            $this->entityManager->persist($shipInPort->ship);
            $this->entityManager->getEventRepo()->logInfection(
                $ship,
                $shipInPort->ship,
                $destinationPort
            );
            $this->entityManager->getUserAchievementRepo()->recordContactWithInfected(
                $shipInPort->ship->owner->id
            );
        }
    }

    /**
     * @param array[] $results
     * @return ShipLocation[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createShipLocationMapper();

        return array_map(static function ($result) use ($mapper) {
            return $mapper->getShipLocation($result);
        }, $results);
    }

    private function getActiveEffectsForShipId(UuidInterface $id): array
    {
        // todo - duplicate from EffectsService. Be tidier
        $mapper = $this->mapperFactory->createActiveEffectMapper();
        return \array_map(static function ($result) use ($mapper) {
            return $mapper->getActiveEffect($result);
        }, $this->entityManager->getActiveEffectRepo()->findActiveForShipId($id));
    }
}
