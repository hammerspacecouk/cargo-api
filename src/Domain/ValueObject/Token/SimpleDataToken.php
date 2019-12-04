<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use function App\Functions\DateTimes\jsonDecode;
use function json_encode;

class SimpleDataToken extends AbstractToken
{
    private const KEY_DATA = 'da';

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function make(
        $data
    ): array {
        return parent::create([
            self::KEY_DATA => json_encode($data, JSON_THROW_ON_ERROR, 512),
        ]);
    }

    public function getData(): array
    {
        return jsonDecode($this->token->get(self::KEY_DATA));
    }
}
