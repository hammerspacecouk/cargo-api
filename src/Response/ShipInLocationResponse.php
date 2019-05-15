<?php
declare(strict_types=1);

namespace App\Response;

use App\Domain\Entity\Ship;
use App\Domain\Entity\ShipInChannel;
use App\Domain\Entity\ShipInPort;
use App\Domain\Entity\User;

class ShipInLocationResponse
{
    private $shipInPortResponse;
    private $shipInChannelResponse;

    public function __construct(
        ShipInChannelResponse $shipInChannelResponse,
        ShipInPortResponse $shipInPortResponse
    ) {
        $this->shipInPortResponse = $shipInPortResponse;
        $this->shipInChannelResponse = $shipInChannelResponse;
    }

    public function getResponseData(User $user, Ship $ship): array
    {
        $location = $ship->getLocation();
        if ($location instanceof ShipInPort) {
            return $this->shipInPortResponse->getResponseData(
                $user,
                $ship,
                $location
            );
        }

        if ($location instanceof ShipInChannel) {
            return $this->shipInChannelResponse->getResponseData(
                $user,
                $ship,
                $location
            );
        }

        throw new \RuntimeException('Unknown location');
    }
}
