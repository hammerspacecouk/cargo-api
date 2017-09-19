<?php declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\Token\AbstractToken;
use JsonSerializable;

abstract class AbstractActionToken extends AbstractToken implements JsonSerializable
{
    public const PATH_PREFIX = '/actions';
    private const TYPE = null;

    public function jsonSerialize()
    {
        return [
            'type' => 'ActionToken',
            'path' => $this->getPath(),
            'token' => (string)$this->getOriginalToken(),
        ];
    }

    public static function getPath()
    {
        return self::PATH_PREFIX . '/' . static::TYPE;
    }
}
