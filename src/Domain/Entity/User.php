<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Score;
use Ramsey\Uuid\UuidInterface;

class User extends Entity implements \JsonSerializable
{
    private $rotationSteps;
    private $score;
    private $homePort;

    public function __construct(
        UuidInterface $id,
        int $rotationSteps,
        Score $score,
        ?Port $homePort
    ) {
        parent::__construct($id);
        $this->rotationSteps = $rotationSteps;
        $this->score = $score;
        $this->homePort = $homePort;
    }

    public function jsonSerialize()
    {
        $data = [
            'id' => $this->getId(),
            'score' => $this->getScore(),
            'colour' => $this->getColour(),
        ];
        if ($this->homePort) {
            $data['homePort'] = $this->getHomePort();
        }
        return $data;
    }

    public function getRotationSteps()
    {
        return $this->rotationSteps;
    }

    public function getScore(): Score
    {
        return $this->score;
    }

    public function getColour(): string
    {
        // get the last 6 characters of the UUID (as they are already hex)
        return '#' . substr((string) $this->id, -6);
    }

    public function getHomePort(): Port
    {
        if ($this->homePort === null) {
            throw new DataNotFetchedException('Data for Home Port was not fetched');
        }
        return $this->homePort;
    }
}
