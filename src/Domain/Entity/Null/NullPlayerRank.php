<?php
declare(strict_types=1);

namespace App\Domain\Entity\Null;

use App\Domain\Entity\PlayerRank;
use Ramsey\Uuid\Uuid;

class NullPlayerRank extends PlayerRank
{
    public function __construct()
    {
        parent::__construct(
            Uuid::fromString(Uuid::NIL),
            '',
            0,
            '',
            ''
        );
    }
}
