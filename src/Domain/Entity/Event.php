<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class Event extends Entity implements \JsonSerializable
{
    public const ACTION_PLAYER_NEW = 'player_new';
    public const ACTION_PLAYER_PROMOTION = 'player_promotion';

    public const ACTION_SHIP_NEW = 'ship_new';
    public const ACTION_SHIP_ARRIVAL = 'ship_arrival';
    public const ACTION_SHIP_DEPARTURE = 'ship_departure';
    public const ACTION_SHIP_RENAME = 'ship_rename';

    private $action;
    private $time;
    private $value;
    private $actioningPlayer;
    private $actioningShip;
    private $rank;
    private $ship;
    private $port;
    private $crate;

    public function __construct(
        UuidInterface $id,
        string $action,
        DateTimeImmutable $time,
        ?string $value,
        ?User $actioningPlayer,
        ?Ship $actioningShip,
        ?PlayerRank $rank,
        ?Ship $ship,
        ?Port $port,
        ?Crate $crate
    ) {
        parent::__construct($id);
        $this->action = $action;
        $this->time = $time;
        $this->value = $value;
        $this->actioningPlayer = $actioningPlayer;
        $this->actioningShip = $actioningShip;
        $this->rank = $rank;
        $this->ship = $ship;
        $this->port = $port;
        $this->crate = $crate;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'time' => $this->time->format('c'),
            'value' => $this->value,
            'actioningPlayer' => $this->actioningPlayer,
            'actioningShip' => $this->actioningShip,
            'rank' => $this->rank,
            'ship' => $this->ship,
            'port' => $this->port,
            'crate' => $this->crate,
        ];
    }
}
