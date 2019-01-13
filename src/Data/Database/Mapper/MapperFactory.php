<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

// Please keep this class in alphabetical order
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

    public function createEffectMapper(): EffectMapper
    {
        return new EffectMapper($this);
    }

    public function createEventMapper(): EventMapper
    {
        return new EventMapper($this);
    }

    public function createPlayerRankMapper(): PlayerRankMapper
    {
        return new PlayerRankMapper($this);
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

    public function createUserAuthenticationMapper(): UserAuthenticationMapper
    {
        return new UserAuthenticationMapper($this);
    }
}
