<?php
declare(strict_types = 1);
namespace App\Domain\Entity;

use Ramsey\Uuid\Uuid;

class Crate extends Entity implements \JsonSerializable
{
    private $status;

    public function __construct(
        Uuid $id,
        string $status,
        string $contents
    ) {
        parent::__construct($id);
        $this->status = $status;
        $this->contents = $contents;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'contents' => $this->contents,
        ];
    }
}