<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Market implements \JsonSerializable
{
    private int $history;
    private int $discovery;
    private int $economy;
    private int $military;

    public function __construct(
        int $history,
        int $discovery,
        int $economy,
        int $military
    ) {
        $this->history = $history;
        $this->discovery = $discovery;
        $this->economy = $economy;
        $this->military = $military;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'history' => 0,
            'discovery' => 0,
            'economy' => 0,
            'military' => 0,
        ];
    }
}
