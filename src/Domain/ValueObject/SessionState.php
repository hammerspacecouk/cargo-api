<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\User;

class SessionState implements \JsonSerializable
{
    private $player;
    private $rankStatus;
    private $loginOptions;

    public function __construct(
        ?User $player = null,
        PlayerRankStatus $rankStatus = null,
        ?LoginOptions $loginOptions = null
    ) {
        $this->player = $player;
        $this->rankStatus = $rankStatus;
        $this->loginOptions = $loginOptions;
    }

    public function jsonSerialize()
    {
        return [
            'isLoggedIn' => (bool)$this->player,
            'player' => $this->player,
            'hasProfileNotification' => $this->player && !$this->player->hasEmailAddress(),
            'rankStatus' => $this->rankStatus,
            'loginOptions' => $this->loginOptions,
        ];
    }
}
