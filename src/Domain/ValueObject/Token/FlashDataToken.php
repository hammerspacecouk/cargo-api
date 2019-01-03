<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use function App\Functions\DateTimes\jsonDecode;

class FlashDataToken extends AbstractToken
{
    private const KEY_DATA = 'da';
    private const KEY_MESSAGES = 'ms';

    public static function make(
        $data,
        array $messages
    ): array {
        return parent::create([
            self::KEY_DATA => \json_encode($data),
            self::KEY_MESSAGES => \json_encode($messages),
        ]);
    }

    public function getData(): array
    {
        return jsonDecode($this->token->get(self::KEY_DATA));
    }

    public function getMessages(): array
    {
        return jsonDecode($this->token->get(self::KEY_MESSAGES));
    }
}
