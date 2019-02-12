<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Bearing;

use App\Domain\ValueObject\Bearing;
use PHPUnit\Framework\Assert;

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

        Assert::assertSame($input, $bearing->getValue());
        Assert::assertSame($input, $bearing->jsonSerialize());
        Assert::assertSame($input, (string)$bearing);

        $opposite = $bearing->getOpposite();

        Assert::assertSame($expectedOpposite, (string)$opposite);
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
