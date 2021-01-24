<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Achievement;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\User;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Arrays\find;

class AchievementService extends AbstractService
{
    /** @return Achievement[] */
    public function findForUser(User $user): array
    {
        $allAchievements = $this->entityManager->getAchievementRepo()->findAll();
        return $this->hydrateAndMap($allAchievements, $user->getId());
    }

    public function findForRank(User $user, PlayerRank $currentRank): array
    {
        $rankAchievements = array_map(static function ($result) {
            return $result['achievement'];
        }, $this->entityManager->getRankAchievementRepo()->findAllForRankId($currentRank->getId()));
        return $this->hydrateAndMap($rankAchievements, $user->getId());
    }

    private function hydrateAndMap(array $results, UuidInterface $userId): array
    {
        $userAchievements = $this->entityManager->getUserAchievementRepo()->findForUserId($userId);
        $mapper = $this->mapperFactory->createAchievementMapper();
        return array_map(static function ($result) use ($userAchievements, $mapper) {
            $userAchievement = find(static function ($achievement) use ($result) {
                return ($achievement['achievement']['id']->equals($result['id']));
            }, $userAchievements);
            if (!$userAchievement && $result['isHidden']) {
                return null;
            }
            if ($userAchievement) {
                $result['collectedAt'] = $userAchievement['collectedAt'];
            }
            return $mapper->getAchievement($result);
        }, $results);
    }
}
