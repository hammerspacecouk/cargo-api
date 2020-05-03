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
    public const ACTION_EFFECT_BLOCKADE = 'effect_blockaded';

    public const ACTION_PLAYER_NEW = 'player_new';
    public const ACTION_PLAYER_PROMOTION = 'player_promotion';

    public const ACTION_SHIP_NEW = 'ship_new';
    public const ACTION_SHIP_ARRIVAL = 'ship_arrival';
    public const ACTION_SHIP_DEPARTURE = 'ship_departure';
    public const ACTION_SHIP_RENAME = 'ship_rename';

    public const ACTION_SHIP_INFECTED = 'ship_infected';
    public const ACTION_SHIP_CURED = 'ship_cured';

    private string $action;
    private DateTimeImmutable $time;
    private ?string $value;
    private ?User $actioningPlayer;
    private ?Ship $actioningShip;
    private ?PlayerRank $rank;
    private ?Ship $ship;
    private ?Port $port;
    private ?Crate $crate;
    private ?Effect $effect;

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

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'time' => DateTimeFactory::toJson($this->time),
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
