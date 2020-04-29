<?php
declare(strict_types=1);

namespace App\Domain\Entity\Null;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Score;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class NullUser extends User
{
    public function __construct()
    {
        parent::__construct(

            Uuid::fromString(Uuid::NIL),
            null,
            0,
            new Score(0, 0, new DateTimeImmutable()),
            '',
            true,
            new DateTimeImmutable(),
            0,
            null,
            null,
        );
    }
}
