<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use function App\Functions\DateTimes\jsonDecode;

class SimpleDataToken extends AbstractToken
{
    private const KEY_DATA = 'da';

    public static function make(
        $data
    ): array {
        return parent::create([
            self::KEY_DATA => \json_encode($data),
        ]);
    }

    public function getData(): array
    {
        return jsonDecode($this->token->get(self::KEY_DATA));
    }
}
