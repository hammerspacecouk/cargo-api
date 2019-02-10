<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use Ramsey\Uuid\UuidInterface;

class CrateLocation extends Entity
{
    private $crate;
    private $isCurrent;

    public function __construct(
        UuidInterface $id,
        bool $isCurrent,
        ?Crate $crate
    ) {
        parent::__construct($id);
        $this->crate = $crate;
        $this->isCurrent = $isCurrent;
    }

    public function getCrate(): Crate
    {
        if (!$this->crate) {
            throw new DataNotFetchedException('Tried to getCrate but it was not fetched');
        }
        return $this->crate;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function toHash(): string
    {
        return \sha1($this->id->toString() . $this->isCurrent);
    }
}
