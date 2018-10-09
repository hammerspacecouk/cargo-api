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
    private $lastUpdated;

    public function __construct(
        UuidInterface $id,
        int $rotationSteps,
        Colour $colour,
        Score $score,
        bool $hasEmailAddress,
        DateTimeImmutable $playStartTime,
        DateTimeImmutable $lastUpdated,
        ?Port $homePort
    ) {
        parent::__construct($id);
        $this->rotationSteps = $rotationSteps;
        $this->score = $score;
        $this->homePort = $homePort;
        $this->hasEmailAddress = $hasEmailAddress;
        $this->playStartTime = $playStartTime;
        $this->lastUpdated = $lastUpdated;
        $this->colour = $colour;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->getId(),
            'score' => $this->getScore(),
            'colour' => $this->getColour(), // todo - remove this line
            'emblem' => $this->getEmblemPath(),
            'startedAt' => $this->getPlayStartTime()->format(DateTimeFactory::FULL),
        ];
        if ($this->homePort) {
            $data['homePort'] = $this->getHomePort();
        }
        return $data;
    }

    public function getEmblemPath(): string
    {
        return '/emblem/' .
            $this->getId() .
            '/' .
            sha1($this->lastUpdated->format(DateTimeFactory::FULL)) .
            '.svg';
    }

    public function getRotationSteps()
    {
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

    public function hasEmailAddress(): bool
    {
        return $this->hasEmailAddress;
    }

    public function getPlayStartTime(): DateTimeImmutable
    {
        return $this->playStartTime;
    }
}
