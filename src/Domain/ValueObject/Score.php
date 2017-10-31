<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;

class Score implements \JsonSerializable
{
    private $score;
    private $scoreRate;
    private $calculationTime;

    public function __construct(
        int $score,
        int $scoreRate,
        DateTimeImmutable $calculationTime
    ) {
        $this->score = $score;
        $this->scoreRate = $scoreRate;
        $this->calculationTime = $calculationTime;
    }

    public function getScore():int
    {
        return $this->score;
    }

    public function getRate(): int
    {
        return $this->scoreRate;
    }

    public function getCalculationTime(): DateTimeImmutable
    {
        return $this->calculationTime;
    }

    public function jsonSerialize()
    {
        return [
            'type' => 'Score',
            'value' => $this->getScore(),
            'rate' => $this->getRate(),
            'datetime' => $this->getCalculationTime()->format('c'),
        ];
    }
}
