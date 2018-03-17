<?php
declare(strict_types=1);

namespace Tests\App\Controller;

use App\Controller\PaginationRequestTrait;
use App\Domain\ValueObject\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaginationRequestTraitTest extends \PHPUnit\Framework\TestCase
{
    /** @var  PaginationRequestTrait */
    private $trait;

    public function setup(): void
    {
        $this->trait = $this->getMockForTrait(PaginationRequestTrait::class);
    }

    /** @dataProvider dataForInvalidValues */
    public function testInvalidValues($input): void
    {
        $request = new Request(['page' => $input]);
        $this->expectException(BadRequestHttpException::class);
        $this->trait->getPageNumber($request);
    }

    public function dataForInvalidValues(): \Generator
    {
        yield [0];
        yield ['0'];
        yield [-1];
        yield ['-1'];
        yield [1.1];
        yield ['1.1'];
        yield ['one'];
        yield [null];
        yield [false];
    }

    public function testUnsetBecomesOne(): void
    {
        $this->assertSame(1, $this->trait->getPageNumber(new Request()));
    }

    /** @dataProvider dataForValidValues */
    public function testValidValues($input, $expectedOutput): void
    {
        $request = new Request(['page' => $input]);
        $this->assertSame($expectedOutput, $this->trait->getPageNumber($request));
    }

    public function dataForValidValues(): \Generator
    {
        yield [1, 1];
        yield ['1', 1];
        yield [2, 2];
        yield ['2', 2];
        yield [PHP_INT_MAX, PHP_INT_MAX];
    }

    public function testGetPaginationOutOfBounds(): void
    {
        $request = new Request();
        $this->expectException(BadRequestHttpException::class);
        $this->trait->getPagination(
            $request,
            2,
            10,
            10
        );
    }

    public function testGetPagination(): void
    {
        $request = new Request();
        $pagination = $this->trait->getPagination(
            $request,
            2,
            10,
            11
        );

        $this->assertInstanceOf(Pagination::class, $pagination);
    }
}
