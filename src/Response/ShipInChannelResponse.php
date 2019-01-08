<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipLocation;
use App\Domain\Entity\User;
use App\Domain\ValueObject\PlayerRankStatus;

class ShipInChannelResponse extends AbstractShipInLocationResponse
{
    public function getResponseDataForLocation(
        array $data,
        User $user,
        Ship $ship,
        ShipLocation $location,
        PlayerRankStatus $rankStatus
    ): array {
        $data['channel'] = $location;
        $data['hint'] = $this->usersService->getUserHint($user, $rankStatus);
        return $data;
    }
}
