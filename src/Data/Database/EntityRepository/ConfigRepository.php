<?php
declare(strict_types=1);

namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\Config;

class ConfigRepository extends AbstractEntityRepository
{
    public function get(): object
    {
        $result = $this->findOneBy([]);
        if (!$result) {
            return (object)[];
        }
        return $result->value;
    }

    public function set(array $value): void
    {
        $result = $this->findOneBy([]);
        if ($result) {
            $result->value = $value;
        } else {
            $result = new Config($value);
        }

        $this->getEntityManager()->persist($result);
        $this->getEntityManager()->flush();
    }
}
