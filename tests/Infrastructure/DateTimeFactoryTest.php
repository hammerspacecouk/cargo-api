<?php
declare(strict_types=1);

namespace Tests\App\Infrastructure;

use App\Infrastructure\DateTimeFactory;

class DateTimeFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testIncludesMicroseconds(): void
    {
        $factory = new DateTimeFactory();
        $time = $factory->now();
        $time2 = $factory->now();

        $this->assertNotEquals($time->format('u'), $time2->format('u'));
    }
}
