<?php
declare(strict_types=1);

namespace Tests\App\Functions;

use function App\Functions\Numbers\clamp;

class NumbersTest extends \PHPUnit\Framework\TestCase
{
    public function testClamp(): void
    {
        $this->assertSame(0, clamp(0, 0, 0));
        $this->assertSame(0, clamp(-1, 0, 0));
        $this->assertSame(0, clamp(-1, 0, 10));
        $this->assertSame(5, clamp(5, 0, 10));
        $this->assertSame(10, clamp(15, 0, 10));
    }
}
