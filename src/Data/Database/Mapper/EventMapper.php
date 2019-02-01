<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Crate;
use App\Domain\Entity\Effect;
use App\Domain\Entity\Event;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\User;

class EventMapper extends Mapper
{
    public function getEvent(array $item): Event
    {
        $domainEntity = new Event(
            $item['id'],
            $item['action'],
            $item['time'],
            $item['value'],
            $this->getUser($item['actioningPlayer']),
            $this->getShip($item['actioningShip']),
            $this->getRank($item['subjectRank']),
            $this->getShip($item['subjectShip']),
            $this->getPort($item['subjectPort']),
            $this->getCrate($item['subjectCrate']),
            $this->getEffect($item['subjectEffect']),
        );
        return $domainEntity;
    }

    private function getUser(?array $data): ?User
    {
        if ($data) {
            return $this->mapperFactory->createUserMapper()->getUser($data);
        }
        return null;
    }

    private function getShip(?array $data): ?Ship
    {
        if ($data) {
            return $this->mapperFactory->createShipMapper()->getShip($data);
        }
        return null;
    }

    private function getPort(?array $data): ?Port
    {
        if ($data) {
            return $this->mapperFactory->createPortMapper()->getPort($data);
        }
        return null;
    }

    private function getRank(?array $data): ?PlayerRank
    {
        if ($data) {
            return $this->mapperFactory->createPlayerRankMapper()->getPlayerRank($data);
        }
        return null;
    }

    private function getCrate(?array $data): ?Crate
    {
        if ($data) {
            return $this->mapperFactory->createCrateMapper()->getCrate($data);
        }
        return null;
    }

    private function getEffect(?array $data): ?Effect
    {
        if ($data) {
            return $this->mapperFactory->createEffectMapper()->getEffect($data);
        }
        return null;
    }
}
