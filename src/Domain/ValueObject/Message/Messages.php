<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Message;

class Messages implements \JsonSerializable
{
    private $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    public function jsonSerialize()
    {
        return $this->messages;
    }

    public function __toString()
    {
        return \base64_encode(\json_encode($this->jsonSerialize()));
    }
}
