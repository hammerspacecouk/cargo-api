<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Colour;
use App\Domain\ValueObject\Score;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class User extends Entity implements \JsonSerializable
{
    private $rotationSteps;
    private $colour;
    private $score;
    private $homePort;
    private $hasEmailAddress;
    private $playStartTime;
    private $playerRank;
    private $permissionLevel;

    public function __construct(
        UuidInterface $id,
        int $rotationSteps,
        Colour $colour,
        Score $score,
        bool $hasEmailAddress,
        DateTimeImmutable $playStartTime,
        int $permissionLevel,
        ?Port $homePort,
        ?PlayerRank $playerRank
    ) {
        parent::__construct($id);
        $this->rotationSteps = $rotationSteps;
        $this->score = $score;
        $this->homePort = $homePort;
        $this->hasEmailAddress = $hasEmailAddress;
        $this->playStartTime = $playStartTime;
        $this->colour = $colour;
        $this->playerRank = $playerRank;
        $this->permissionLevel = $permissionLevel;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->getId(),
            'score' => $this->getScore(),
            'startedAt' => $this->getPlayStartTime()->format(DateTimeFactory::FULL),
        ];
        if ($this->homePort) {
            $data['homePort'] = $this->getHomePort();
        }
        if ($this->playerRank) {
            $data['emblem'] = $this->getEmblemPath();
            $data['rank'] = $this->getRank();
        }
        return $data;
    }

    public function getEmblemPath(): string
    {
        return '/emblem/' . $this->getRank()->getId()->toString() .
            '-' . $this->getRank()->getEmblemHash() .
            '-' . $this->getColour() .
            '.svg';
    }

    public function getRotationSteps()
    {
        $this->rotationSteps = 0; // todo - remove this line
        return $this->rotationSteps;
    }

    public function getScore(): Score
    {
        return $this->score;
    }

    public function getColour(): Colour
    {
        return $this->colour;
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

    public function hasEmailAddress(): bool
    {
        return $this->hasEmailAddress;
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
