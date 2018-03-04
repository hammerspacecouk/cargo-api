<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\Token\Action\AbstractActionToken;

class Transaction implements \JsonSerializable
{
    private $cost;
    private $token;

    public function __construct(
        int $cost,
        AbstractActionToken $token
    ) {
        $this->cost = $cost;
        $this->token = $token;
    }

    public function jsonSerialize()
    {
        return [
            'cost' => $this->cost,
            'actionToken' => $this->token,
        ];
    }

    public function getToken(): AbstractActionToken
    {
        return $this->token;
    }

    public function getCost(): int
    {
        return $this->cost;
    }
}
