<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Channel;
use App\Domain\ValueObject\Bearing;

class ChannelMapper extends Mapper
{
    public function getChannel(array $item): Channel
    {
        $portMapper = $this->mapperFactory->createPortMapper();

        return new Channel(
            $item['id'],
            $portMapper->getPort($item['fromPort']),
            $portMapper->getPort($item['toPort']),
            new Bearing($item['bearing']),
            $item['distance']
        );
    }
}
