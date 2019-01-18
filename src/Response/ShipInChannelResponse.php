<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;

class ShipInChannelResponse extends AbstractShipInLocationResponse
{
    public function getResponseData(
        User $user,
        Ship $ship,
        ShipLocation $location,
        array $bonusEffects = null
    ): array {
        $data = $this->getBaseData($user, $ship, $location);

        $data['channel'] = $location;
        $data['hint'] = $this->usersService->getUserHint($user);

        if ($bonusEffects !== null) {
            $data['bonus'] = $bonusEffects;
        }

        return $data;
    }
}
