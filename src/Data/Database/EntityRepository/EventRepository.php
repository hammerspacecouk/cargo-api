<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Event;
use App\Data\Database\Entity\PlayerRank;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\Ship;
use App\Data\Database\Entity\User;
use App\Domain\Entity\Event as DomainEvent;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\UuidInterface;

class EventRepository extends AbstractEntityRepository
{
    private const DEFAULT_PAGE_SIZE = 25;

    public function getAllLatest(
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->buildSelect($limit, $offset);
        return $qb->getQuery()->getResult($resultType);
    }

    public function getLatestForUserId(
        UuidInterface $userId,
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        $resultType = Query::HYDRATE_ARRAY
    ) {
        $qb = $this->buildSelect($limit, $offset)
            ->where('IDENTITY(tbl.actioningPlayer) = :userId')
            ->orWhere('IDENTITY(actioningShip.owner) = :userId')
            ->orWhere('IDENTITY(ship.owner) = :userId')
            ->setParameter('userId', $userId->getBytes());
        return $qb->getQuery()->getResult($resultType);
    }

    public function getLatestForPortId(
        UuidInterface $userId,
        int $limit = self::DEFAULT_PAGE_SIZE,
        int $offset = 0,
        $resultType = Query::HYDRATE_ARRAY
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
            function (Event $entity) use ($player, $homePort) {
                $entity->actioningPlayer = $player;
                $entity->subjectPort = $homePort;
                return $entity;
            }
        );
    }

    public function logNewShip(Ship $ship, User $player): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_NEW,
            function (Event $entity) use ($ship, $player) {
                $entity->actioningPlayer = $player;
                $entity->subjectShip = $ship;
                return $entity;
            }
        );
    }

    public function logPromotion(User $player, PlayerRank $rank): Event
    {
        return $this->log(
            DomainEvent::ACTION_PLAYER_PROMOTION,
            function (Event $entity) use ($rank, $player) {
                $entity->actioningPlayer = $player;
                $entity->subjectRank = $rank;
                return $entity;
            }
        );
    }

    public function logShipArrival(Ship $ship, Port $port): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_ARRIVAL,
            function (Event $entity) use ($ship, $port) {
                $entity->actioningShip = $ship;
                $entity->subjectPort = $port;
                return $entity;
            }
        );
    }

    public function logShipRename(Ship $ship, string $oldName): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_RENAME,
            function (Event $entity) use ($ship, $oldName) {
                $entity->subjectShip = $ship;
                $entity->value = $oldName;
                return $entity;
            }
        );
    }

    public function logShipDeparture(Ship $ship, Port $port): Event
    {
        return $this->log(
            DomainEvent::ACTION_SHIP_DEPARTURE,
            function (Event $entity) use ($ship, $port) {
                $entity->actioningShip = $ship;
                $entity->subjectPort = $port;
                return $entity;
            }
        );
    }

    private function buildSelect(
        int $limit,
        int $offset
    ): QueryBuilder {
        return $this->createQueryBuilder('tbl')
            ->select(
                'tbl',
                'actioningPlayer',
                'actioningShip',
                'actioningShipOwner',
                'playerRank',
                'ship',
                'shipOwner',
                'port',
                'crate'
            )
            ->leftJoin('tbl.actioningPlayer', 'actioningPlayer')
            ->leftJoin('tbl.actioningShip', 'actioningShip')
            ->leftJoin('actioningShip.owner', 'actioningShipOwner')
            ->leftJoin('tbl.subjectRank', 'playerRank')
            ->leftJoin('tbl.subjectShip', 'ship')
            ->leftJoin('ship.owner', 'shipOwner')
            ->leftJoin('tbl.subjectPort', 'port')
            ->leftJoin('tbl.subjectCrate', 'crate')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('tbl.time', 'DESC');
    }

    private function log(string $eventType, callable $values): Event
    {
        $entity = new Event(
            $this->currentTime,
            $eventType
        );

        $entity = $values($entity);

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);

        $this->logger->notice('[GAME_EVENT] [' . $eventType . ']');

        return $entity;
    }
}
