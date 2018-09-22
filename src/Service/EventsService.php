<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Mapper\EventMapper;
use App\Domain\Entity\Event;
use App\Domain\Entity\Port;
use App\Domain\Entity\User;

class EventsService extends AbstractService
{
    private $eventMapper;

    /**
     * @return Event[]
     */
    public function findAllLatest(): array
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getAllLatest());
    }

    /**
     * @param User $user
     * @return Event[]
     */
    public function findLatestForUser(User $user): array
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getLatestForUserId($user->getId()));
    }

    public function findLatestForPort(Port $port)
    {
        return $this->mapMany($this->entityManager->getEventRepo()->getLatestForPortId($port->getId()));
    }


    private function getMapper(): EventMapper
    {
        if (!$this->eventMapper) {
            $this->eventMapper = $this->mapperFactory->createEventMapper();
        }
        return $this->eventMapper;
    }

    private function mapSingle(?array $result): ?Event
    {
        return $result ? $this->getMapper()->getEvent($result) : null;
    }

    /**
     * @param array $results
     * @return User[]
     */
    private function mapMany(array $results): array
    {
        return \array_map(['self', 'mapSingle'], $results);
    }
}
