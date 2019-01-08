<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class Effect implements \JsonSerializable
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $expiry;

    public function __construct(
      ?\DateTimeImmutable $expiry = null
    ) {
        $this->expiry = $expiry;
    }

    public function jsonSerialize()
    {
    }
}
