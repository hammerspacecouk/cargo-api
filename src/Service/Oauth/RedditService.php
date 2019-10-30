<?php
declare(strict_types=1);

namespace App\Service\Oauth;

use App\Data\Database\Entity\User as DbUser;

class RedditService extends AbstractOAuthService
{
    public function getHashQuery(): string
    {
        return 'tbl.redditId = :hash';
    }

    public function attachHash(DbUser $entity, $hash): DbUser
    {
        $entity->redditId = $hash;
        return $entity;
    }
}
