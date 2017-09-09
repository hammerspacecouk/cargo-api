<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

class MapperFactory
{
    public function createChannelMapper(): ChannelMapper
    {
        return new ChannelMapper($this);
    }

    public function createCrateMapper(): CrateMapper
    {
        return new CrateMapper($this);
    }

    public function createPortMapper(): PortMapper
    {
        return new PortMapper($this);
    }

    public function createShipMapper(): ShipMapper
    {
        return new ShipMapper($this);
    }

    public function createShipClassMapper(): ShipClassMapper
    {
        return new ShipClassMapper($this);
    }

    public function createShipLocationMapper(): ShipLocationMapper
    {
        return new ShipLocationMapper($this);
    }

    public function createUserMapper(): UserMapper
    {
        return new UserMapper($this);
    }
}
