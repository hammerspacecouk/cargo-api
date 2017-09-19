<?php declare(strict_types=1);

namespace Tests\App\Domain\ValueObject;

use App\Domain\ValueObject\ShipClass;

class ShipClassTest extends \PHPUnit\Framework\TestCase
{
    public function testValues()
    {
        $shipClass = new ShipClass(
            $name = 'ShipClassName',
            $capacity = 10
        );

        $this->assertSame($name, $shipClass->getName());
        $this->assertSame($capacity, $shipClass->getCapacity());

        $output = $shipClass->jsonSerialize();

        $this->assertTrue(is_array($output));
        $this->assertCount(3, $output); // no more, no less

        // check all the keys (their position in the array doesn't matter)
        $this->assertSame('ShipClass', $output['type']);
        $this->assertSame($name, $output['name']);
        $this->assertSame($capacity, $output['capacity']);
    }
}
