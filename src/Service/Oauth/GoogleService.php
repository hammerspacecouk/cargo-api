<?php
declare(strict_types=1);

namespace App\Service\Oauth;

use App\Data\Database\Entity\User as DbUser;

class GoogleService extends AbstractOAuthService
{
    public function getHashQuery(): string
    {
        return 'tbl.googleId = :hash';
    }

    public function attachHash(DbUser $entity, string $hash): DbUser
    {
        $entity->googleId = $hash;
        return $entity;
    }
}
