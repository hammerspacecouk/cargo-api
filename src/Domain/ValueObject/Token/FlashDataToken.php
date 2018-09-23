<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

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

    public function getData()
    {
        return \json_decode($this->token->get(self::KEY_DATA), true);
    }

    public function getMessages()
    {
        return \json_decode($this->token->get(self::KEY_MESSAGES), true);
    }
}
