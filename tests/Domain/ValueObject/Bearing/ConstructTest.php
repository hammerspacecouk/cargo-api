<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Bearing;

use App\Domain\ValueObject\Bearing;

class ConstructTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidBearing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Bearing('NOT VALID');
    }

    /** @dataProvider valuesDataProvider */
    public function testValues(string $input, string $expectedOpposite): void
    {
        $bearing = new Bearing($input);

        $this->assertSame($input, $bearing->getValue());
        $this->assertSame($input, $bearing->jsonSerialize());
        $this->assertSame($input, (string)$bearing);

        $opposite = $bearing->getOpposite();
        $this->assertInstanceOf(Bearing::class, $opposite);

        $this->assertSame($expectedOpposite, (string)$opposite);
    }

    public function valuesDataProvider(): \Generator
    {
        yield ['NW', 'SE',];
        yield ['NE', 'SW',];
        yield ['E', 'W',];
        yield ['SE', 'NW',];
        yield ['SW', 'NE',];
        yield ['W', 'E',];
    }
}
