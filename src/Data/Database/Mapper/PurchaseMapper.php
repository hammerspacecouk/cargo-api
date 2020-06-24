<?php
declare(strict_types=1);

namespace App\Data\Database\Mapper;

use App\Domain\Entity\Purchase;

class PurchaseMapper extends Mapper
{
    public function getPurchase(array $item): Purchase
    {
        $user = null;
        if (array_key_exists('user', $item)) {
            $user = $this->mapperFactory->createUserMapper()->getUser($item['user']);
        }

        return new Purchase(
            $item['id'],
            $item['productId'],
            $item['createdAt'],
            $item['cost'],
            $item['vat'],
            $user
        );
    }
}
