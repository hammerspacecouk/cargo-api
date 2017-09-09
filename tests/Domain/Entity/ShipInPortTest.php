<?php
declare(strict_types=1);

namespace Tests\App\Domain\Entity;

use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInPort;
use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\Uuid;

class ShipInPortTest extends \PHPUnit\Framework\TestCase
{
    private const EXAMPLE_TIME = '2017-09-09T19:19:19+00:00';

    public function testNoShip()
    {
        $entity = $this->getUnfetched();
        $this->expectException(DataNotFetchedException::class);
        $entity->getShip();
    }

    public function testNoPort()
    {
        $entity = $this->getUnfetched();
        $this->expectException(DataNotFetchedException::class);
        $entity->getPort();
    }

    public function testValues()
    {
        $ship = $this->createMock(Ship::class);
        $port = $this->createMock(Port::class);

        $entity = new ShipInPort(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            $ship,
            $time = new \DateTimeImmutable(self::EXAMPLE_TIME),
            $port
        );

        $this->assertSame($id, $entity->getId());
        $this->assertSame($time, $entity->getEntryTime());
        $this->assertSame($ship, $entity->getShip());
        $this->assertSame($port, $entity->getPort());
        $this->assertSame($port, $entity->jsonSerialize());
    }

    private function getUnfetched(): ShipInPort
    {
        return new ShipInPort(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            null,
            new \DateTimeImmutable(self::EXAMPLE_TIME),
            null
        );
    }
}
