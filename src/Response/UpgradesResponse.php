<?php
declare(strict_types=1);

namespace App\Response;

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
            'defence' => $this->upgradesService->getAvailableDefenceForUser($user),
            'weapons' => $this->upgradesService->getAvailableWeaponsForUser($user),
            'navigation' => $this->upgradesService->getAvailableTravelAbilitiesForUser($user),
        ];
    }
}
