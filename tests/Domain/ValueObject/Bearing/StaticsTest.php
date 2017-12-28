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
    public function testGetRotatedBearing($input, $steps, $expectedOutput)
    {
        $output = Bearing::getRotatedBearing($input, $steps);
        $this->assertSame($expectedOutput, $output);
    }

    public function dataForRotatedBearing()
    {
        return [
            [
                'NW',
                0,
                'NW',
            ],
            [
                'NW',
                1,
                'NE',
            ],
            [
                'NW',
                2,
                'E',
            ],
            [
                'NW',
                3,
                'SE',
            ],
            [
                'NW',
                4,
                'SW',
            ],
            [
                'NW',
                5,
                'W',
            ],

            [
                'NE',
                0,
                'NE',
            ],
            [
                'NE',
                1,
                'E',
            ],
            [
                'NE',
                2,
                'SE',
            ],
            [
                'NE',
                3,
                'SW',
            ],
            [
                'NE',
                4,
                'W',
            ],
            [
                'NE',
                5,
                'NW',
            ],

            [
                'E',
                0,
                'E',
            ],
            [
                'E',
                1,
                'SE',
            ],
            [
                'E',
                2,
                'SW',
            ],
            [
                'E',
                3,
                'W',
            ],
            [
                'E',
                4,
                'NW',
            ],
            [
                'E',
                5,
                'NE',
            ],

            [
                'SE',
                0,
                'SE',
            ],
            [
                'SE',
                1,
                'SW',
            ],
            [
                'SE',
                2,
                'W',
            ],
            [
                'SE',
                3,
                'NW',
            ],
            [
                'SE',
                4,
                'NE',
            ],
            [
                'SE',
                5,
                'E',
            ],

            [
                'SW',
                0,
                'SW',
            ],
            [
                'SW',
                1,
                'W',
            ],
            [
                'SW',
                2,
                'NW',
            ],
            [
                'SW',
                3,
                'NE',
            ],
            [
                'SW',
                4,
                'E',
            ],
            [
                'SW',
                5,
                'SE',
            ],

            [
                'W',
                0,
                'W',
            ],
            [
                'W',
                1,
                'NW',
            ],
            [
                'W',
                2,
                'NE',
            ],
            [
                'W',
                3,
                'E',
            ],
            [
                'W',
                4,
                'SE',
            ],
            [
                'W',
                5,
                'SW',
            ],
        ];
    }
}
