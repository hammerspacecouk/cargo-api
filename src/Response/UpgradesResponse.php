<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\User;
use App\Service\UpgradesService;

class UpgradesResponse
{
    private UpgradesService $upgradesService;

    public function __construct(
        UpgradesService $upgradesService
    ) {
        $this->upgradesService = $upgradesService;
    }

    /**
     * @param User $user
     * @return array<string, mixed>
     */
    public function getResponseDataForUser(User $user): array
    {
        return [
            'ships' => $this->upgradesService->getAvailableShipsForUser($user),
        ];
    }
}
