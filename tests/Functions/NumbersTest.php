<?php
declare(strict_types=1);

namespace Tests\App\Functions;

use function App\Functions\Numbers\clamp;
use PHPUnit\Framework\Assert;

class NumbersTest extends \PHPUnit\Framework\TestCase
{
    public function testClamp(): void
    {
        Assert::assertSame(0, clamp(0, 0, 0));
        Assert::assertSame(0, clamp(-1, 0, 0));
        Assert::assertSame(0, clamp(-1, 0, 10));
        Assert::assertSame(5, clamp(5, 0, 10));
        Assert::assertSame(10, clamp(15, 0, 10));
    }
}
