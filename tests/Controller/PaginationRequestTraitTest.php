<?php
declare(strict_types=1);

namespace Tests\App\Controller;

use App\Controller\PaginationRequestTrait;
use App\Domain\ValueObject\Pagination;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaginationRequestTraitTest extends \PHPUnit\Framework\TestCase
{
    /** @var  PaginationRequestTrait|PHPUnit_Framework_MockObject_MockObject */
    private $trait;

    public function setup()
    {
        $this->trait = $this->getMockForTrait(PaginationRequestTrait::class);
    }

    /** @dataProvider dataForInvalidValues */
    public function testInvalidValues($input)
    {
        $request = new Request(['page' => $input]);
        $this->expectException(BadRequestHttpException::class);
        $this->trait->getPageNumber($request);
    }

    public function dataForInvalidValues()
    {
        return [
            [0],
            ['0'],
            [-1],
            ['-1'],
            [1.1],
            ['1.1'],
            ['one'],
            [null],
            [false],
        ];
    }

    public function testUnsetBecomesOne()
    {
        $this->assertSame(1, $this->trait->getPageNumber(new Request()));
    }


    /** @dataProvider dataForValidValues */
    public function testValidValues($input, $expectedOutput)
    {
        $request = new Request(['page' => $input]);
        $this->assertSame($expectedOutput, $this->trait->getPageNumber($request));
    }

    public function dataForValidValues()
    {
        return [
            [1, 1],
            ['1', 1],
            [2, 2],
            ['2', 2],
            [PHP_INT_MAX, PHP_INT_MAX],
        ];
    }

    public function testGetPaginationOutOfBounds()
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

    public function testGetPagination()
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
