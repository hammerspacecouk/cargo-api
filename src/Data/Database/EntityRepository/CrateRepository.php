<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\User;

class CrateRepository extends AbstractEntityRepository
{
    public function newCrate(
        string $contents,
        int $value,
        ?User $reservedForPlayer = null
    ): Crate {
        $crate = new Crate(
            $contents,
            $value
        );
        if ($reservedForPlayer) {
            $crate->reservedFor = $reservedForPlayer;
        }

        $this->getEntityManager()->persist($crate);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->getEventRepo()->logNewCrate($contents, $reservedForPlayer);
        return $crate;
    }
}
