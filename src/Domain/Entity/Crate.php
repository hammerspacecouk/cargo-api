<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use App\Domain\ValueObject\Enum\CrateStatus;
use Ramsey\Uuid\Uuid;

class Crate extends Entity implements \JsonSerializable
{
    private $status = CrateStatus::INACTIVE;

    private $contents;

    public function __construct(
        Uuid $id,
        CrateStatus $status,
        string $contents
    ) {
        parent::__construct($id);
        $this->status = $status->getValue();
        $this->contents = $contents;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => 'Crate',
            'status' => $this->status,
            'contents' => $this->contents,
        ];
    }
}
