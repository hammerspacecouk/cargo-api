<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\Token\FlashDataToken;

class LoginOptions implements \JsonSerializable
{
    private $loginAnonToken;
    private $loginEmailToken;
    private $facebook;
    private $google;
    private $microsoft;
    private $twitter;

    public function __construct(
        ?FlashDataToken $loginAnonToken = null,
        ?FlashDataToken $loginEmailToken = null,
        bool $facebook = false,
        bool $google = false,
        bool $microsoft = false,
        bool $twitter = false
    ) {
        $this->loginAnonToken = $loginAnonToken;
        $this->loginEmailToken = $loginEmailToken;
        $this->facebook = $facebook;
        $this->google = $google;
        $this->microsoft = $microsoft;
        $this->twitter = $twitter;
    }

    public function jsonSerialize()
    {
        return [
            'anon' => $this->loginAnonToken ? (string)$this->loginAnonToken : null,
            'email' => $this->loginEmailToken ? (string)$this->loginEmailToken : null,
            'facebook' => $this->facebook,
            'google' => $this->google,
            'microsoft' => $this->microsoft,
            'twitter' => $this->twitter,
        ];
    }
}
