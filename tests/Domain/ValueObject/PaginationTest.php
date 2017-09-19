<?php declare(strict_types=1);

namespace Tests\App\Domain\ValueObject;

use App\Domain\ValueObject\Pagination;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    public function testOutOfBoundsZero()
    {
        $pagination = new Pagination(0, 10, 10);
        $this->assertTrue($pagination->isOutOfBounds());
    }

    public function testOutOfBoundsNegative()
    {
        $pagination = new Pagination(-1, 10, 10);
        $this->assertTrue($pagination->isOutOfBounds());
    }

    public function testOutOfBoundsTooHigh()
    {
        $pagination = new Pagination(2, 10, 10);
        $this->assertTrue($pagination->isOutOfBounds());
    }

    public function testPageData()
    {
        $pagination = new Pagination(2, 10, 100, '/page');
        $this->assertFalse($pagination->isOutOfBounds());

        $output = $pagination->jsonSerialize();
        $this->assertTrue(is_array($output));
        $this->assertCount(8, $output); // no more, no less

        // check all the keys (their position in the array doesn't matter)
        $this->assertSame(100, $output['total']);
        $this->assertSame(10, $output['perPage']);
        $this->assertSame(2, $output['currentPage']);
        $this->assertSame(10, $output['totalPages']);
        $this->assertSame(1, $output['previousPage']);
        $this->assertSame(3, $output['nextPage']);
        $this->assertSame('/page', $output['previousPagePath']);
        $this->assertSame('/page?page=3', $output['nextPagePath']);
    }

    public function testFirstPage()
    {
        $pagination = new Pagination(1, 10, 100, '/page');
        $output = $pagination->jsonSerialize();
        $this->assertNull($output['previousPage']);
        $this->assertNull($output['previousPagePath']);
    }

    public function testLastPage()
    {
        $pagination = new Pagination(10, 10, 100, '/page');
        $output = $pagination->jsonSerialize();
        $this->assertNull($output['nextPage']);
        $this->assertNull($output['nextPagePath']);
    }
}
