<?php
declare(strict_types=1);

namespace App\Service\Oauth;

use App\Data\Database\Entity\User as DbUser;

class MicrosoftService extends AbstractOAuthService
{
    public function getHashQuery(): string
    {
        return 'tbl.microsoftId = :hash';
    }

    public function attachHash(DbUser $entity, $hash): DbUser
    {
        $entity->microsoftId = $hash;
        return $entity;
    }
}
