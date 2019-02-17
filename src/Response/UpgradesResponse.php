<?php
declare(strict_types=1);

namespace App\Response;

use App\Data\Database\Types\EnumEffectsDisplayGroupType;
use App\Domain\Entity\User;
use App\Service\UpgradesService;

class UpgradesResponse
{
    private $upgradesService;

    public function __construct(
        UpgradesService $upgradesService
    ) {
        $this->upgradesService = $upgradesService;
    }

    public function getResponseDataForUser(User $user): array
    {
        return [
            'ships' => $this->upgradesService->getAvailableShipsForUser($user),
            'effects' => [
                'travel' =>
                    $this->upgradesService->getAvailableEffectsByDisplayTypeForUser(
                        $user,
                        EnumEffectsDisplayGroupType::TYPE_TRAVEL
                    ),
                'defence' =>
                    $this->upgradesService->getAvailableEffectsByDisplayTypeForUser(
                        $user,
                        EnumEffectsDisplayGroupType::TYPE_DEFENCE
                    ),
                'offence' =>
                    $this->upgradesService->getAvailableEffectsByDisplayTypeForUser(
                        $user,
                        EnumEffectsDisplayGroupType::TYPE_OFFENCE
                    ),
                'special' =>
                    $this->upgradesService->getAvailableEffectsByDisplayTypeForUser(
                        $user,
                        EnumEffectsDisplayGroupType::TYPE_SPECIAL
                    ),
            ],
        ];
    }
}
