<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\ValueObject\Token\AbstractToken;
use JsonSerializable;

abstract class AbstractActionToken extends AbstractToken implements JsonSerializable
{
    public const PATH_PREFIX = '/actions';

    public function jsonSerialize()
    {
        return [
            'path' => static::getPath(),
            'token' => (string)$this,
        ];
    }

    public static function getPath()
    {
        return self::PATH_PREFIX . '/' . static::getSubject();
    }
}
