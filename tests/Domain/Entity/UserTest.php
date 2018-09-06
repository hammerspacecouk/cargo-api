<?php
declare(strict_types=1);

namespace Tests\App\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Score;
use Ramsey\Uuid\Uuid;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testValues(): void
    {
        $user = new User(
            $id = Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            2,
            $score = $this->createMock(Score::class),
            false,
            null
        );

        $this->assertSame($id, $user->getId());
        $this->assertSame($score, $user->getScore());
        $this->assertSame(2, $user->getRotationSteps());
        $this->assertFalse($user->hasEmailAddress());
    }

    public function testSame(): void
    {
        $score = $this->createMock(Score::class);
        $entity = new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            2,
            $score,
            false,
            null
        );
        $matchingEntity = new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000000'),
            2,
            $score,
            false,
            null
        );
        $secondEntity =  new User(
            Uuid::fromString('00000000-0000-4000-0000-000000000002'),
            3,
            $score,
            false,
            null
        );

        $this->assertTrue($entity->equals($matchingEntity));
        $this->assertFalse($entity->equals($secondEntity));
    }
}
