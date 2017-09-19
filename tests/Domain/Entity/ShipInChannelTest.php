<?php declare(strict_types=1);

namespace Tests\App\Domain\Entity;

use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInChannel;
use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\Uuid;

class ShipInChannelTest extends \PHPUnit\Framework\TestCase
{
    private const ENTRY_TIME = '2017-09-09T19:19:19+00:00';
    private const EXIT_TIME = '2017-09-09T20:19:19+00:00';

    public function testNoShip()
    {
        $entity = $this->getUnfetched();

        $this->expectException(DataNotFetchedException::class);
        $entity->getShip();
    }

    public function testNoOrigin()
    {
        $entity = $this->getUnfetched();

        $this->expectException(DataNotFetchedException::class);
        $entity->getOrigin();
    }

    public function testNoDestination()
    {
        $entity = $this->getUnfetched();

        $this->expectException(DataNotFetchedException::class);
        $entity->getDestination();
    }

    public function testValues()
    {
        $ship = $this->createMock(Ship::class);
        $origin = $this->createMock(Port::class);
        $destination = $this->createMock(Port::class);

        $entity = new ShipInChannel(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            $ship,
            $entry = new \DateTimeImmutable(self::ENTRY_TIME),
            $exit = new \DateTimeImmutable(self::EXIT_TIME),
            $origin,
            $destination
        );

        $this->assertSame($id, $entity->getId());
        $this->assertSame($entry, $entity->getEntryTime());
        $this->assertSame($exit, $entity->getExitTime());
        $this->assertSame($ship, $entity->getShip());
        $this->assertSame($origin, $entity->getOrigin());
        $this->assertSame($destination, $entity->getDestination());
        $this->assertSame('TRAVELLING', $entity->jsonSerialize());
    }

    private function getUnfetched(): ShipInChannel
    {
        return new ShipInChannel(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            null,
            new \DateTimeImmutable(self::ENTRY_TIME),
            new \DateTimeImmutable(self::EXIT_TIME),
            null,
            null
        );
    }
}
