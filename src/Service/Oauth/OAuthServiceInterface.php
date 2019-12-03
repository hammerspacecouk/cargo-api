<?php
declare(strict_types=1);

namespace App\Service\Oauth;

use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\User;

interface OAuthServiceInterface
{
    public function attachToUser(User $user, string $id): User;
    public function userExistsForOAuthId(string $id): bool;
    public function getOrCreateUserForOAuthId(string $id): User;
    public function getHashQuery(): string;
    public function attachHash(DbUser $entity, string $hash): DbUser;
}
