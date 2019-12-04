<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Mapper\EventMapper;
use App\Domain\Entity\Event;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;

class EventsService extends AbstractService
{
    /**
     * @var EventMapper|null
     */
    private $eventMapper;

    /**
     * @param int $limit
     * @return Event[]
     */
    public function findAllLatest(int $limit = 25): array
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getAllLatest($limit));
    }

    /**
     * @param User $user
     * @return Event[]
     */
    public function findLatestForUser(User $user): array
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getLatestForUserId($user->getId()));
    }

    /**
     * @param Port $port
     * @return Event[]
     */
    public function findLatestForPort(Port $port): array
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getLatestForPortId($port->getId(), 10));
    }


    private function getMapper(): EventMapper
    {
        if (!$this->eventMapper) {
            $this->eventMapper = $this->mapperFactory->createEventMapper();
        }
        return $this->eventMapper;
    }

    /**
     * @param array[]|null $result
     * @return Event|null
     */
    private function mapSingle(?array $result): ?Event
    {
        return $result ? $this->getMapper()->getEvent($result) : null;
    }

    /**
     * @param array[] $results
     * @return Event[]
     */
    private function mapMany(array $results): array
    {
        return array_map(function ($result) {
            return $this->mapSingle($result);
        }, $results);
    }
}
