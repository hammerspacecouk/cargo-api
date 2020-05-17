<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\CleanableInterface;
use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\Effect;
use App\Data\Database\Entity\Event;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\User;
use App\Domain\Entity\Event as DomainEvent;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\UuidInterface;

class EventRepository extends AbstractEntityRepository implements CleanableInterface
{
    private const DEFAULT_PAGE_SIZE = 15;

    /**
     * @param int $limit
     * @param int $offset
     * @param int $resultType
     * @return mixed
     */
    public function getAllLatest(
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->buildSelect($limit, $offset);
        return $qb->getQuery()->getResult($resultType);
    }

    /**
     * @param UuidInterface $userId
     * @param int $limit
     * @param int $offset
     * @param int $resultType
     * @return mixed
     */
    public function getLatestForUserId(
        UuidInterface $userId,
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->buildSelect($limit, $offset)
            ->where('IDENTITY(tbl.actioningPlayer) = :userId')
            ->orWhere('IDENTITY(actioningShip.owner) = :userId')
            ->orWhere('IDENTITY(ship.owner) = :userId')
            ->setParameter('userId', $userId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function deleteForUserId(UuidInterface $userId): void
    {
        $this->createQueryBuilder('tbl')
            ->delete(Event::class, 'tbl')
            ->where('IDENTITY(tbl.actioningPlayer) = :userId')
            ->setParameter('userId', $userId->getBytes())
            ->getQuery()
            ->execute();
    }

    /**
     * @param UuidInterface $userId
     * @param int $limit
     * @param int $offset
     * @param int $resultType
     * @return mixed
     */
    public function getLatestForPortId(
        UuidInterface $userId,
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        int $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->buildSelect($limit, $offset)
            ->where('IDENTITY(tbl.subjectPort) = :portId')
            ->setParameter('portId', $userId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function logNewPlayer(User $player, Port $homePort): Event
    {
        return $this->log(
            DomainEvent::ACTION_PLAYER_NEW,
            static function (Event $entity) use ($player, $homePort) {
                $entity->actioningPlayer = $player;
                $entity->subjectPort = $homePort;
                return $entity;
            },
        );
    }

    public function logNewShip(Ship $ship, User $player): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_NEW,
            static function (Event $entity) use ($ship, $player) {
                $entity->actioningPlayer = $player;
                $entity->subjectShip = $ship;
                $entity->subjectPort = $player->homePort;
                return $entity;
            },
        );
    }

    public function logPromotion(User $player, PlayerRank $rank): Event
    {
        return $this->log(
            DomainEvent::ACTION_PLAYER_PROMOTION,
            static function (Event $entity) use ($rank, $player) {
                $entity->actioningPlayer = $player;
                $entity->subjectRank = $rank;
                return $entity;
            },
        );
    }

    public function logShipArrival(Ship $ship, Port $port): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_ARRIVAL,
            static function (Event $entity) use ($ship, $port) {
                $entity->actioningShip = $ship;
                $entity->subjectPort = $port;
                return $entity;
            },
        );
    }

    public function logShipRename(Ship $ship, string $oldName): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_RENAME,
            static function (Event $entity) use ($ship, $oldName) {
                $entity->subjectShip = $ship;
                $entity->value = $oldName;
                return $entity;
            },
        );
    }

    public function logShipDeparture(Ship $ship, Port $port): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_DEPARTURE,
            static function (Event $entity) use ($ship, $port) {
                $entity->actioningShip = $ship;
                $entity->subjectPort = $port;
                return $entity;
            },
        );
    }

    public function logNewCrate(Crate $crate, User $reservedForPlayer): Event
    {
        return $this->log(
            DomainEvent::ACTION_CRATE_NEW,
            static function (Event $entity) use ($crate, $reservedForPlayer) {
                $entity->subjectCrate = $crate;
                $entity->actioningPlayer = $reservedForPlayer;
                return $entity;
            },
        );
    }

    public function logCratePickup(Crate $crate, Ship $ship, Port $port): Event
    {
        return $this->log(
            DomainEvent::ACTION_CRATE_PICKUP,
            static function (Event $entity) use ($crate, $ship, $port) {
                $entity->subjectPort = $port;
                $entity->subjectCrate = $crate;
                $entity->actioningShip = $ship;
                return $entity;
            },
        );
    }

    public function logUseEffect(
        Effect $effect,
        User $actioningPlayer,
        ?Ship $affectedShip = null,
        ?Port $affectedPort = null
    ): Event {
        return $this->log(
            DomainEvent::ACTION_EFFECT_USE,
            static function (Event $entity) use ($effect, $actioningPlayer, $affectedShip, $affectedPort) {
                $entity->actioningPlayer = $actioningPlayer;
                $entity->subjectEffect = $effect;
                $entity->subjectPort = $affectedPort;
                $entity->subjectShip = $affectedShip;
                return $entity;
            },
        );
    }

    public function logOffence(
        Ship $attackingShip,
        Port $inPort,
        Ship $affectedShip,
        Effect $effect,
        int $damage
    ): Event {
        return $this->log(
            ($damage >= $affectedShip->strength) ?
                DomainEvent::ACTION_EFFECT_DESTROYED : DomainEvent::ACTION_EFFECT_OFFENCE,
            static function (Event $entity) use ($attackingShip, $affectedShip, $inPort, $effect, $damage) {
                $entity->actioningShip = $attackingShip;
                $entity->subjectShip = $affectedShip;
                $entity->subjectPort = $inPort;
                $entity->subjectEffect = $effect;
                $entity->value = (string)$damage;
                return $entity;
            },
        );
    }

    public function logInfection(
        Ship $infected,
        Ship $affected,
        Port $inPort
    ): Event {
        return $this->log(
            DomainEvent::ACTION_SHIP_INFECTED,
            static function (Event $entity) use ($infected, $affected, $inPort) {
                $entity->actioningShip = $infected;
                $entity->subjectShip = $affected;
                $entity->subjectPort = $inPort;
                return $entity;
            },
        );
    }

    public function logCured(
        Ship $affected,
        Port $inPort
    ): Event {
        return $this->log(
            DomainEvent::ACTION_SHIP_CURED,
            static function (Event $entity) use ($affected, $inPort) {
                $entity->subjectShip = $affected;
                $entity->subjectPort = $inPort;
                return $entity;
            },
        );
    }

    public function logBlockade(
        User $actioningPlayer,
        Port $affectedPort
    ): Event {
        return $this->log(
            DomainEvent::ACTION_EFFECT_BLOCKADE,
            static function (Event $entity) use ($actioningPlayer, $affectedPort) {
                $entity->actioningPlayer = $actioningPlayer;
                $entity->subjectPort = $affectedPort;
                return $entity;
            },
        );
    }

    public function clean(\DateTimeImmutable $now): int
    {
        return $this->removeOld($now);
    }

    private function buildSelect(
        int $limit,
        int $offset
    ): QueryBuilder {
        return $this->createQueryBuilder('tbl')
            ->select(
                'tbl',
                'actioningPlayer',
                'actioningPlayerRank',
                'actioningShip',
                'actioningShipOwner',
                'playerRank',
                'ship',
                'shipOwner',
                'port',
                'crate',
                'effect',
                'actioningShipClass',
                'targetShipClass',
            )
            ->leftJoin('tbl.actioningPlayer', 'actioningPlayer')
            ->leftJoin('actioningPlayer.lastRankSeen', 'actioningPlayerRank')
            ->leftJoin('tbl.actioningShip', 'actioningShip')
            ->leftJoin('actioningShip.owner', 'actioningShipOwner')
            ->leftJoin('tbl.subjectRank', 'playerRank')
            ->leftJoin('tbl.subjectShip', 'ship')
            ->leftJoin('ship.owner', 'shipOwner')
            ->leftJoin('tbl.subjectPort', 'port')
            ->leftJoin('tbl.subjectCrate', 'crate')
            ->leftJoin('tbl.subjectEffect', 'effect')
            ->leftJoin('actioningShip.shipClass', 'actioningShipClass')
            ->leftJoin('ship.shipClass', 'targetShipClass')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('tbl.time', 'DESC');
    }

    private function log(string $eventType, callable $values): Event
    {
        $entity = new Event(
            DateTimeFactory::now(),
            $eventType,
        );

        $entity = $values($entity);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        $this->logger->notice('[GAME_EVENT] [' . $eventType . ']');

        return $entity;
    }

    private function removeOld(DateTimeImmutable $now): int
    {
        $sql = 'DELETE FROM ' . Event::class . ' t WHERE t.time < :before';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('before', $now->sub(new DateInterval('P3M')));
        return $query->execute();
    }
}
