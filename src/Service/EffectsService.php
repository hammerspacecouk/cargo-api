<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Types\EnumEffectsType;
use App\Domain\Entity\Effect;
use App\Domain\Entity\User;

class EffectsService extends AbstractService
{
    public function getWeaponsForUser(User $user): array
    {
        return $this->getByTypeForUser(EnumEffectsType::TYPE_OFFENCE, $user);
    }

    public function countOwnedForUser(Effect $effect, User $user): int
    {
        return $this->entityManager->getUserEffectRepo()->countForUserId($effect->getId(), $user->getId());
    }

    private function getByTypeForUser(string $type, User $user): array
    {
        $all = $this->mapMany(
            $this->entityManager->getEffectRepo()->getAllByType($type)
        );

        // set the ones you're not allowed to get to null
        return \array_map(function(Effect $effect) use ($user) {
            if ($user->getRank()->meets($effect->getMinimumRank())) {
                return $effect;
            }
            return null;
        }, $all);
    }

    /**
     * @param array $results
     * @return Effect[]
     */
    private function mapMany(array $results): array
    {
        $mapper = $this->mapperFactory->createEffectMapper();
        return \array_map(function(array $result) use ($mapper) {
            return $mapper->getEffect($result);
        }, $results);
    }
}
