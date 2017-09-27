<?php
declare(strict_types=1);

namespace Tests\App\Domain\Entity;

use App\Domain\Entity\User;
use Ramsey\Uuid\Uuid;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testValues()
    {
        $user = new User(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            'user@example.com',
            2
        );

        $this->assertSame($id, $user->getId());
        $this->assertSame(2, $user->getRotationSteps());
        $this->assertNull($user->jsonSerialize());
    }

    public function testSame()
    {
        $entity = new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            'user@example.com',
            2
        );
        $matchingEntity = new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            'user@example.com',
            2
        );
        $secondEntity =  new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000002'),
            'user2@example.com',
            3
        );

        $this->assertTrue($entity->equals($matchingEntity));
        $this->assertFalse($entity->equals($secondEntity));
    }
}
