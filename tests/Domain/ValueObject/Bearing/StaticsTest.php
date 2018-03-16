<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Bearing;

use App\Domain\ValueObject\Bearing;

class StaticsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetInitialRandomNumber()
    {
        $this->markTestSkipped('Randomness comes later');
        $maxAttempts = 1000;
        $attempts = 0;
        $found = [];

        // run this many times, ensuring that every number generated is an int between 0 and 5
        while ($attempts < $maxAttempts) {
            $step = Bearing::getInitialRandomStepNumber();
            $found[$step] = true;

            if (count($found) === 6) {
                break;
            }
            $attempts++;
        }

        if ($attempts === $maxAttempts) {
            $this->fail(
                'Not all the possible steps were generated after ' . $maxAttempts . '. Did it lose randomness?'
            );
        }

        ksort($found);
        $this->assertSame([0, 1, 2, 3, 4, 5], array_keys($found));
    }

    public function testGetEmptyBearingList()
    {
        $bearings = Bearing::getEmptyBearingsList();
        $this->assertSame([
            'NW' => null,
            'NE' => null,
            'E' => null,
            'SE' => null,
            'SW' => null,
            'W' => null,
        ], $bearings);
    }

    /** @dataProvider dataForRotatedBearing */
    public function testGetRotatedBearing(string $input, int $steps, string $expectedOutput)
    {
        $output = Bearing::getRotatedBearing($input, $steps);
        $this->assertSame($expectedOutput, $output);
    }

    public function dataForRotatedBearing(): \Generator
    {
        yield ['NW', 0, 'NW',];
        yield ['NW', 1, 'NE',];
        yield ['NW', 2, 'E',];
        yield ['NW', 3, 'SE',];
        yield ['NW', 4, 'SW',];
        yield ['NW', 5, 'W',];

        yield ['NE', 0, 'NE',];
        yield ['NE', 1, 'E',];
        yield ['NE', 2, 'SE',];
        yield ['NE', 3, 'SW',];
        yield ['NE', 4, 'W',];
        yield ['NE', 5, 'NW',];

        yield ['E', 0, 'E',];
        yield ['E', 1, 'SE',];
        yield ['E', 2, 'SW',];
        yield ['E', 3, 'W',];
        yield ['E', 4, 'NW',];
        yield ['E', 5, 'NE',];

        yield ['SE', 0, 'SE',];
        yield ['SE', 1, 'SW',];
        yield ['SE', 2, 'W',];
        yield ['SE', 3, 'NW',];
        yield ['SE', 4, 'NE',];
        yield ['SE', 5, 'E',];

        yield ['SW', 0, 'SW',];
        yield ['SW', 1, 'W',];
        yield ['SW', 2, 'NW',];
        yield ['SW', 3, 'NE',];
        yield ['SW', 4, 'E',];
        yield ['SW', 5, 'SE',];

        yield ['W', 0, 'W',];
        yield ['W', 1, 'NW',];
        yield ['W', 2, 'NE',];
        yield ['W', 3, 'E',];
        yield ['W', 4, 'SE',];
        yield ['W', 5, 'SW',];
    }
}
