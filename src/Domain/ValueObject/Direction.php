<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\Channel;
use App\Domain\Entity\PlayerRank;
use App\Domain\Entity\Port;
use App\Domain\Entity\Ship;
use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;

class Direction implements \JsonSerializable
{
    private Port $destinationPort;
    private Channel $channel;
    private PlayerRank $playerRank;
    private int $time;
    private ?int $earnings;
    private ?DateTimeImmutable $lastVisitTime;
    private bool $isHomePort;
    private ?int $blockadeStrength;
    private ?int $yourStrength;

    /**
     * @var string[]
     */
    private array $denialReasons = [];
    /**
     * @var Ship[]
     */
    private array $convoyShips;

    public function __construct(
        Port $destinationPort,
        Channel $channel,
        PlayerRank $playerRank,
        Ship $ship,
        bool $isHomePort,
        int $time,
        ?int $earnings = null,
        ?DateTimeImmutable $lastVisitTime = null,
        array $convoyShips = [],
        ?int $blockadeStrength = null,
        ?int $yourStrength = null
    ) {
        $this->destinationPort = $destinationPort;
        $this->channel = $channel;
        $this->playerRank = $playerRank;
        $this->time = $time;
        $this->earnings = $earnings;
        $this->isHomePort = $isHomePort;
        $this->lastVisitTime = $lastVisitTime;
        $this->convoyShips = $convoyShips;
        $this->blockadeStrength = $blockadeStrength;
        $this->yourStrength = $yourStrength;

        if (empty($this->convoyShips)) {
            $this->convoyShips = [$ship];
        }

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
            'lastVisitTime' => DateTimeFactory::toJson($this->lastVisitTime),
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
            return;
        }

        $totalStrength = 0;
        foreach ($this->convoyShips as $convoyShip) {
            $totalStrength += $convoyShip->getStrength();

            // starter ship can only go to known or safe territory
            if ($this->lastVisitTime === null &&
                !$this->destinationPort->isSafe() &&
                $convoyShip->getShipClass()->isStarterShip()
            ) {
                $this->denialReasons[] = 'Too risky for Reticulum Shuttle';
            }

            // only the starter ship can get to the goal
            if ($this->destinationPort->isGoal() && !$convoyShip->getShipClass()->isStarterShip()) {
                $this->denialReasons[] = 'The Reticulum Shuttle must make this journey alone';
            }
        }

        if ($minimumStrength && $totalStrength < $minimumStrength) {
            $this->denialReasons[] = sprintf(
                'This ship/convoy is not currently strong enough for this journey (%d/%d)',
                $totalStrength,
                $minimumStrength
            );
        }

        // if yourStrength is not set then it's your blockade. also doesn't apply until level 120
        if ($this->yourStrength !== null &&
            $this->blockadeStrength !== null &&
            $this->yourStrength < $this->blockadeStrength &&
            $this->playerRank->getThreshold() >= 120
        ) {
            $this->denialReasons[] = sprintf(
                'Your total ship strength here is not a match for the Blockade strength (%d/%d)',
                $this->yourStrength,
                $this->blockadeStrength
            );
        }

        $this->denialReasons = array_unique($this->denialReasons);
    }
}
