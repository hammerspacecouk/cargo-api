<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Score;
use Ramsey\Uuid\UuidInterface;

class User extends Entity implements \JsonSerializable
{
    private $rotationSteps;
    private $score;

    public function __construct(
        UuidInterface $id,
        int $rotationSteps,
        Score $score
    ) {
        parent::__construct($id);
        $this->rotationSteps = $rotationSteps;
        $this->score = $score;
    }

    public function jsonSerialize()
    {
        // the User object must not be exposed
        return null;
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
}
