<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Crate;
use App\Data\Database\Entity\User;

class CrateRepository extends AbstractEntityRepository
{
    public function makeInitialCrateForPlayer(
        User $reservedForPlayer
    ): Crate {
        $crateContents = $this->getEntityManager()->getCrateTypeRepo()->getRandomInitialCrateContents();

        $crate = new Crate(
            $crateContents->contents,
            $crateContents->value
        );
        $crate->reservedFor = $reservedForPlayer;

        $this->getEntityManager()->persist($crate);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->getEventRepo()->logNewCrate($crate, $reservedForPlayer);
        return $crate;
    }

    public function newRandomCrate(): Crate {
        $crateContents = $this->getEntityManager()->getCrateTypeRepo()->getRandomCrateContents();

        $crate = new Crate(
            $crateContents->contents,
            $crateContents->value
        );

        $this->getEntityManager()->persist($crate);
        $this->getEntityManager()->flush();
        return $crate;
    }

    public function removeReservation(Crate $crate): void
    {
        // if this crate was previously reserved, open it up to the world
        // otherwise, do nothing
        if ($crate->reservedFor) {
            $crate->reservedFor = null;
            $this->getEntityManager()->persist($crate);
            $this->getEntityManager()->flush();
        }
    }
}
