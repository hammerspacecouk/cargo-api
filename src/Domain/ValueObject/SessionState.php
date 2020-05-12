<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\User;

class SessionState implements \JsonSerializable
{
    private ?User $player;
    private ?PlayerRankStatus $rankStatus;

    public function __construct(
        ?User $player = null,
        PlayerRankStatus $rankStatus = null
    ) {
        $this->player = $player;
        $this->rankStatus = $rankStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'isLoggedIn' => (bool)$this->player,
            'player' => $this->player,
            'rankStatus' => $this->rankStatus,
        ];
    }
}
