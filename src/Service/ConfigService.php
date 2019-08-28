<?php
declare(strict_types=1);

namespace App\Service;

class ConfigService extends AbstractService
{
    public function getConfig()
    {
        return $this->entityManager->getConfigRepo()->get();
    }

    public function setConfig($value)
    {
        return $this->entityManager->getConfigRepo()->set($value);
    }
}
