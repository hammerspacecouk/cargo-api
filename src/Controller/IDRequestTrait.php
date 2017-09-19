<?php declare(strict_types=1);

namespace App\Controller;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait IDRequestTrait
{
    public function getID(Request $request): UuidInterface
    {
        $uuid = $request->get('uuid');
        if (!$uuid || !Uuid::isValid($uuid)) {
            throw new BadRequestHttpException('Invalid ID');
        }

        return Uuid::fromString($uuid);
    }
}
