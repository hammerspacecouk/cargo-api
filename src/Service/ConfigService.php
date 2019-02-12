<?php
declare(strict_types=1);

namespace App\Service;

class ConfigService extends AbstractService
{
    private const KEY_ALPHA_EMAILS = 'alphaEmails';

    public function emailExistsInAlphaList(string $email): bool
    {
        $email = $this->sanitiseEmail($email);
        $alphaEmails = $this->getConfigKey(self::KEY_ALPHA_EMAILS);
        if (!$alphaEmails) {
            return false;
        }
        return \in_array($email, $alphaEmails, true);
    }

    public function getConfig()
    {
        return $this->entityManager->getConfigRepo()->get();
    }

    public function setConfig($value)
    {
        return $this->entityManager->getConfigRepo()->set($value);
    }

    private function sanitiseEmail(string $input): string
    {
        return \strtolower(\trim($input));
    }

    private function getConfigKey(string $key)
    {
        $config = $this->entityManager->getConfigRepo()->get();
        return $config[$key] ?? null;
    }
}
