<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\Token\AbstractToken;
use JsonSerializable;

abstract class AbstractActionToken extends AbstractToken implements JsonSerializable
{
    private const TYPE = null;
    private const PATH_PREFIX = '/play/action/';

    public function getPath()
    {
        return self::PATH_PREFIX . static::TYPE;
    }

    public function jsonSerialize()
    {
        return [
            'type' => 'ActionToken',
            'path' => $this->getPath(),
            'token' => (string) $this->getOriginalToken(),
        ];
    }
}