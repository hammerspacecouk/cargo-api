<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Score;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use function strtoupper;

class User extends Entity implements \JsonSerializable
{
    private $rotationSteps;
    private $score;
    private $homePort;
    private $isAnonymous;
    private $playStartTime;
    private $playerRank;
    private $permissionLevel;
    private $emblem;
    private $displayName;

    public function __construct(
        UuidInterface $id,
        ?string $displayName,
        int $rotationSteps,
        Score $score,
        string $emblem,
        bool $isAnonymous,
        DateTimeImmutable $playStartTime,
        int $permissionLevel,
        ?Port $homePort,
        ?PlayerRank $playerRank
    ) {
        parent::__construct($id);
        $this->rotationSteps = $rotationSteps;
        $this->score = $score;
        $this->homePort = $homePort;
        $this->isAnonymous = $isAnonymous;
        $this->playStartTime = $playStartTime;
        $this->playerRank = $playerRank;
        $this->permissionLevel = $permissionLevel;
        $this->emblem = $emblem;
        $this->displayName = $displayName;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->getId(),
            'displayName' => $this->getDisplayName(),
            'score' => $this->getScore(),
            'startedAt' => $this->getPlayStartTime()->format(DateTimeFactory::FULL),
            'emblem' => $this->getEmblemPath(),
        ];
        if ($this->homePort) {
            $data['homePort'] = $this->getHomePort();
        }
        if ($this->playerRank) {
            $data['rank'] = $this->getRank();
        }
        return $data;
    }

    public function getDisplayName(): string
    {
        if ($this->displayName) {
            return $this->displayName;
        }
        return 'Recruit #' . strtoupper($this->getId()->getTimeLowHex());
    }

    public function getEmblemPath(): string
    {
        return '/emblem/' . $this->getId()->toString() .
            '-' . $this->getEmblemHash() .
            '.svg';
    }

    public function getEmblem(): string
    {
        return $this->emblem;
    }

    public function getEmblemHash(): string
    {
        return \md5($this->emblem); // used as a cache buster. needs to be quick, not secure
    }

    /**
     * @return int
     */
    public function getRotationSteps(): int
    {
        return $this->rotationSteps;
    }

    public function getScore(): Score
    {
        return $this->score;
    }

    public function getHomePort(): Port
    {
        if ($this->homePort === null) {
            throw new DataNotFetchedException('Data for Home Port was not fetched');
        }
        return $this->homePort;
    }

    public function getRank(): PlayerRank
    {
        if ($this->playerRank === null) {
            throw new DataNotFetchedException('Data for Player Rank was not fetched');
        }
        return $this->playerRank;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function getPlayStartTime(): DateTimeImmutable
    {
        return $this->playStartTime;
    }

    public function isAdmin(): bool
    {
        return $this->permissionLevel === 100; // todo - other levels?
    }
}
