<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class Event extends Entity implements \JsonSerializable
{
    public const ACTION_CRATE_NEW = 'crate_new';
    public const ACTION_CRATE_PICKUP = 'crate_pickup';

    public const ACTION_EFFECT_USE = 'effect_use';
    public const ACTION_EFFECT_OFFENCE = 'effect_offence';
    public const ACTION_EFFECT_DESTROYED = 'effect_destroyed';

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
    private $effect;

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
        ?Crate $crate,
        ?Effect $effect
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
        $this->effect = $effect;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'time' => $this->time->format(DateTimeFactory::FULL),
            'value' => $this->value,
            'actioningPlayer' => $this->actioningPlayer,
            'actioningShip' => $this->actioningShip,
            'rank' => $this->rank,
            'ship' => $this->ship,
            'port' => $this->port,
            'crate' => $this->crate,
            'effect' => $this->effect,
        ];
    }
}
