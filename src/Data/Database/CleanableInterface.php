<?php
declare(strict_types=1);

namespace App\Data\Database;

interface CleanableInterface
{
    public function clean(\DateTimeImmutable $now): int;
}
