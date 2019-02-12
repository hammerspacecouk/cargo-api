<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject;

use App\Domain\ValueObject\Pagination;
use PHPUnit\Framework\Assert;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    public function testOutOfBoundsZero(): void
    {
        $pagination = new Pagination(0, 10, 10);
        Assert::assertTrue($pagination->isOutOfBounds());
    }

    public function testOutOfBoundsNegative(): void
    {
        $pagination = new Pagination(-1, 10, 10);
        Assert::assertTrue($pagination->isOutOfBounds());
    }

    public function testOutOfBoundsTooHigh(): void
    {
        $pagination = new Pagination(2, 10, 10);
        Assert::assertTrue($pagination->isOutOfBounds());
    }

    public function testPageData(): void
    {
        $pagination = new Pagination(2, 10, 100, '/page');
        Assert::assertFalse($pagination->isOutOfBounds());

        $output = $pagination->jsonSerialize();
        Assert::assertCount(8, $output); // no more, no less

        // check all the keys (their position in the array doesn't matter)
        Assert::assertSame(100, $output['total']);
        Assert::assertSame(10, $output['perPage']);
        Assert::assertSame(2, $output['currentPage']);
        Assert::assertSame(10, $output['totalPages']);
        Assert::assertSame(1, $output['previousPage']);
        Assert::assertSame(3, $output['nextPage']);
        Assert::assertSame('/page', $output['previousPagePath']);
        Assert::assertSame('/page?page=3', $output['nextPagePath']);
    }

    public function testFirstPage(): void
    {
        $pagination = new Pagination(1, 10, 100, '/page');
        $output = $pagination->jsonSerialize();
        Assert::assertNull($output['previousPage']);
        Assert::assertNull($output['previousPagePath']);
    }

    public function testLastPage(): void
    {
        $pagination = new Pagination(10, 10, 100, '/page');
        $output = $pagination->jsonSerialize();
        Assert::assertNull($output['nextPage']);
        Assert::assertNull($output['nextPagePath']);
    }
}
