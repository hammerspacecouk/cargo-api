<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Token\FlashDataToken;

class SessionState implements \JsonSerializable
{
    private $player;
    private $rankStatus;
    private $loginToken;

    public function __construct(
        ?User $player = null,
        PlayerRankStatus $rankStatus = null,
        ?FlashDataToken $loginToken = null
    ) {
        $this->player = $player;
        $this->rankStatus = $rankStatus;
        $this->loginToken = $loginToken;
    }

    public function jsonSerialize()
    {
        return [
            'isLoggedIn' => (bool)$this->player,
            'player' => $this->player,
            'hasProfileNotification' => $this->player && !$this->player->hasEmailAddress(),
            'rankStatus' => $this->rankStatus,
            'loginToken' => $this->loginToken ? (string)$this->loginToken : null,
        ];
    }
}
