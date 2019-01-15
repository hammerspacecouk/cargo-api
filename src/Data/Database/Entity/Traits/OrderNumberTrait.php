<?php
declare(strict_types=1);

namespace App\Data\Database\Entity\Traits;

trait OrderNumberTrait
{
    /** @ORM\Column(type="integer", unique=true) */
    public $orderNumber;
}
