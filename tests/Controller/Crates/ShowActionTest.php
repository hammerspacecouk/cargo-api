<?php
declare(strict_types=1);

namespace Tests\App\Controller\Crates;

use App\Controller\Crates\ShowAction;
use App\Domain\Entity\Crate;
use App\Service\CratesService;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowActionTest extends \PHPUnit\Framework\TestCase
{
    private const EXAMPLE_UUID = '00000000-0000-4000-0000-000000000000';

    /** @var PHPUnit_Framework_MockObject_MockObject|CratesService */
    private $mockCratesService;

    /** @var PHPUnit_Framework_MockObject_MockObject|LoggerInterface */
    private $mockLogger;

    public function setup()
    {
        $this->mockCratesService = $this->createMock(CratesService::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function testNoSuchCrate()
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request([
            'uuid' => self::EXAMPLE_UUID
        ]);

        $uuid = Uuid::fromString(self::EXAMPLE_UUID);
        $this->mockCratesService->expects($this->once())
            ->method('getByIDWithLocation')
            ->with($uuid)
            ->willReturn(null);

        $controller = new ShowAction;
        $controller($request, $this->mockCratesService, $this->mockLogger);
    }

    public function testValidResponse()
    {
        $request = new Request([
            'uuid' => self::EXAMPLE_UUID
        ]);

        $crate = $this->createMock(Crate::class);
        $crate->method('jsonSerialize')
            ->willReturn('crateData');

        $uuid = Uuid::fromString(self::EXAMPLE_UUID);
        $this->mockCratesService->expects($this->once())
            ->method('getByIDWithLocation')
            ->with($uuid)
            ->willReturn($crate);

        $controller = new ShowAction;
        $response = $controller($request, $this->mockCratesService, $this->mockLogger);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('"crateData"', $response->getContent());
    }
}
