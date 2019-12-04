<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

abstract class Message implements \JsonSerializable
{
    public const WEIGHTING = 0;
    protected const TYPE = null;

    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->getTypeString(),
            'message' => $this->message,
        ];
    }

    public function getWeighting(): int
    {
        return static::WEIGHTING;
    }

    private function getTypeString(): ?string
    {
        return static::TYPE;
    }
}
