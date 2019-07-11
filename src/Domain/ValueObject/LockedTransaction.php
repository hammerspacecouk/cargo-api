<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\PlayerRank;

class LockedTransaction extends Transaction
{
    private $minimumRank;

    public function __construct(
        PlayerRank $minimumRank
    ) {
        parent::__construct();
        $this->minimumRank = $minimumRank;
    }

    public function jsonSerialize()
    {
        return [
            'available' => false,
            'requirement' => 'Minimum Rank: ' . $this->minimumRank->getName(),
        ];
    }
}
