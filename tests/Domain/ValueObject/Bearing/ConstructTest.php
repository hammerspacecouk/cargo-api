<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Bearing;

use App\Domain\ValueObject\Bearing;

class ConstructTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidBearing()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Bearing('NOT VALID');
    }

    /** @dataProvider valuesDataProvider */
    public function testValues(string $input, string $expectedOpposite)
    {
        $bearing = new Bearing($input);

        $this->assertSame($input, $bearing->getValue());
        $this->assertSame($input, $bearing->jsonSerialize());
        $this->assertSame($input, (string)$bearing);

        $opposite = $bearing->getOpposite();
        $this->assertInstanceOf(Bearing::class, $opposite);

        $this->assertSame($expectedOpposite, (string)$opposite);
    }

    public function valuesDataProvider(): array
    {
        return [
            [
                'NW',
                'SE',
            ],
            [
                'NE',
                'SW',
            ],
            [
                'E',
                'W',
            ],
            [
                'SE',
                'NW',
            ],
            [
                'SW',
                'NE',
            ],
            [
                'W',
                'E',
            ],
        ];
    }
}
