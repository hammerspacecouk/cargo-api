<?php
declare(strict_types=1);

namespace App\Service;

class ConfigService extends AbstractService
{
    public function getConfig(): object
    {
        return $this->entityManager->getConfigRepo()->get();
    }

    /**
     * @param array<mixed> $value
     */
    public function setConfig(array $value): void
    {
        $this->entityManager->getConfigRepo()->set($value);
    }
}
