<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\Channel;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Exception\DataNotFetchedException;
use DateInterval;
use DateTimeImmutable;

class Direction implements \JsonSerializable
{
    private $destinationPort;
    private $channel;
    private $playerRank;
    private $ship;
    private $time;
    private $earnings;
    private $lastVisitTime;

    /**
     * @var string[]
     */
    private $denialReasons = [];
    /**
     * @var bool
     */
    private $isHomePort;

    public function __construct(
        Port $destinationPort,
        Channel $channel,
        PlayerRank $playerRank,
        Ship $ship,
        bool $isHomePort,
        int $time,
        int $earnings = null,
        DateTimeImmutable $lastVisitTime = null
    ) {
        $this->destinationPort = $destinationPort;
        $this->channel = $channel;
        $this->playerRank = $playerRank;
        $this->ship = $ship;
        $this->time = $time;
        $this->earnings = $earnings;
        $this->isHomePort = $isHomePort;
        $this->lastVisitTime = $lastVisitTime;

        $this->calculateEligibility();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'destination' => $this->destinationPort,
            'distanceUnit' => $this->channel->getDistance(),
            'earnings' => $this->earnings,
            'journeyTimeSeconds' => $this->isAllowedToEnter() ? $this->time : null,
            'isAllowed' => $this->isAllowedToEnter(),
            'denialReason' => $this->getDenialReason(),
            'isHomePort' => $this->isHomePort,
            'lastVisitTime' => $this->lastVisitTime ? $this->lastVisitTime->format('c') : null,
        ];
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function isAllowedToEnter(): bool
    {
        return empty($this->denialReasons);
    }

    public function getDenialReason(): ?string
    {
        if (empty($this->denialReasons)) {
            return null;
        }
        return \implode(', ', $this->denialReasons);
    }

    public function getJourneyTime(): int
    {
        return $this->time;
    }

    public function getJourneyTimeInterval(): DateInterval
    {
        return new DateInterval('PT' . $this->time . 'S');
    }

    public function getEarnings(): int
    {
        if ($this->earnings === null) {
            throw new DataNotFetchedException('Did not calculate earnings');
        }
        return $this->earnings;
    }

    public function hasVisited(): bool
    {
        return $this->lastVisitTime !== null;
    }

    public function getLastVisitTime(): ?DateTimeImmutable
    {
        return $this->lastVisitTime;
    }

    private function calculateEligibility(): void
    {
        $minimumRank = $this->channel->getMinimumRank();
        $minimumStrength = $this->channel->getMinimumStrength();
        if (!$this->playerRank->meets($minimumRank)) {
            $this->denialReasons[] = 'Minimum Rank: ' . $minimumRank->getName();
        }
        if ($minimumStrength && !$this->ship->meetsStrength($minimumStrength)) {
            $this->denialReasons[] = 'This ship is not currently strong enough for this journey';
        }
    }
}
