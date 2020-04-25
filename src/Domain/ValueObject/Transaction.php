<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Token\Action\AbstractActionToken;

class Transaction implements \JsonSerializable
{
    private ?int $cost;
    private ?AbstractActionToken $token;
    private int $currentCount;
    /** @var mixed */
    private $item;

    /**
     * @param int|null $cost
     * @param AbstractActionToken|null $token
     * @param int $currentCount
     * @param null|mixed $item
     */
    public function __construct(
        int $cost = null,
        AbstractActionToken $token = null,
        int $currentCount = 0,
        $item = null
    ) {
        $this->cost = $cost;
        $this->token = $token;
        $this->currentCount = $currentCount;
        $this->item = $item;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'available' => true,
            'cost' => $this->cost,
            'actionToken' => $this->token,
            'currentCount' => $this->currentCount,
        ];

        if ($this->item) {
            $data['detail'] = $this->item;
        }

        return $data;
    }

    public function getToken(): AbstractActionToken
    {
        return $this->token;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    /**
     * @return mixed|null
     */
    public function getItemDetail()
    {
        if ($this->item === null) {
            throw new DataNotFetchedException(
                'Tried to get Transaction Item Detail, but it was not fetched'
            );
        }
        return $this->item;
    }
}
