<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Null\NullUser;
use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

class Purchase extends Entity implements JsonSerializable
{
    public const PRODUCTS = [
        Purchase::PRODUCT_FULL_ACCOUNT => 'price_1ICoRpHmbr44mHgjYNYr3RND',
        Purchase::PRODUCT_NEW_SHUTTLE => 'price_1IAdfFHmbr44mHgjmPTlxy3z',
    ];
    public const PRODUCT_FULL_ACCOUNT = 'full_account';
    public const PRODUCT_NEW_SHUTTLE = 'new_shuttle';
    private string $productId;
    private DateTimeImmutable $purchaseTime;
    private int $totalCost;
    private int $vatCost;
    /**
     * @var User|null
     */
    private ?User $user;

    public function __construct(
        UuidInterface $id,
        string $productId,
        DateTimeImmutable $purchaseTime,
        int $totalCost,
        int $vatCost,
        ?User $user
    ) {
        parent::__construct($id);
        $this->productId = $productId;
        $this->purchaseTime = $purchaseTime;
        $this->totalCost = $totalCost;
        $this->vatCost = $vatCost;
        $this->user = $user;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'product' => $this->getProductName(),
            'datetime' => DateTimeFactory::toJson($this->purchaseTime),
            'total' => $this->getTotal(),
        ];
    }

    public function getProductName(): string
    {
        return match ($this->productId) {
            self::PRODUCTS[self::PRODUCT_FULL_ACCOUNT] => 'Full Game',
            self::PRODUCTS[self::PRODUCT_NEW_SHUTTLE] => 'Reticulum Shuttle (continue)',
        default => 'Misc',
        };
    }

    public function getPurchaseTime(): DateTimeImmutable
    {
        return $this->purchaseTime;
    }

    public function getCost(): string
    {
        return '£' . number_format(($this->totalCost - $this->vatCost) / 100, 2);
    }

    public function getTax(): string
    {
        return '£' . number_format($this->vatCost / 100, 2);
    }

    public function getTotal(): string
    {
        return '£' . number_format($this->totalCost / 100, 2);
    }

    public function getUserId(): string
    {
        $user = $this->getUser();
        if ($user) {
            return $user->getId()->toString();
        }
        return 'DELETED-USER';
    }

    public function getUser(): ?User
    {
        if ($this->user === null) {
            throw new DataNotFetchedException('Tried to get Ship but the data was not fetched');
        }
        if ($this->user instanceof NullUser) {
            return null;
        }
        return $this->user;
    }
}
