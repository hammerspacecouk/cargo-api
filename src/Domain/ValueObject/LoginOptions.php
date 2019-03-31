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
        $data = [];
        if ($this->loginAnonToken) {
            $data['anon'] = [
                'path' => '/login/anonymous',
                'token' => (string)$this->loginAnonToken,
            ];
        }
        if ($this->loginEmailToken) {
            $data['email'] = [
                'path' => '/login/email',
                'token' => (string)$this->loginEmailToken,
            ];
        }

        if ($this->facebook) {
            $data['facebook'] = '/login/facebook';
        }
        if ($this->google) {
            $data['google'] = '/login/google';
        }
        if ($this->microsoft) {
            $data['microsoft'] = '/login/microsoft';
        }
        if ($this->twitter) {
            $data['twitter'] = '/login/twitter';
        }

        return $data;
    }
}
