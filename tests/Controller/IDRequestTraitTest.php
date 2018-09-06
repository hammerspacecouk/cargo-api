<?php
declare(strict_types=1);

namespace Tests\App\Controller;

use App\Controller\IDRequestTrait;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IDRequestTraitTest extends \PHPUnit\Framework\TestCase
{
    private const EXAMPLE_UUID = '00000000-0000-4000-0000-000000000000';

    /** @var  IDRequestTrait */
    private $trait;

    public function setup(): void
    {
        $this->trait = $this->getMockForTrait(IDRequestTrait::class);
    }

    public function testNoUuidInRequest(): void
    {
        $request = new Request();
        $this->expectException(BadRequestHttpException::class);
        $this->trait->getID($request);
    }

    public function testInvalidUuidInRequest(): void
    {
        $request = new Request([
            'uuid' => '1234',
        ]);
        $this->expectException(BadRequestHttpException::class);
        $this->trait->getID($request);
    }

    public function testGetId(): void
    {
        $request = new Request([
            'uuid' => self::EXAMPLE_UUID,
        ]);
        $uuid = $this->trait->getID($request);

        $this->assertInstanceOf(UuidInterface::class, $uuid);
        $this->assertSame(self::EXAMPLE_UUID, (string)$uuid);
    }
}
