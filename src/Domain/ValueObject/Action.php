<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject;

class Action implements \JsonSerializable
{
    public const REQUEST_SHIP_NAME = 100;

    public const VALID_ACTIONS = [
        self::REQUEST_SHIP_NAME,
    ];

    public function __construct()
    {
    }

    public function getToken()
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'action' => null,
        ];
    }
}
