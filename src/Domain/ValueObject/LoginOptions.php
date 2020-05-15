<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\Token\SimpleDataToken;

class LoginOptions implements \JsonSerializable
{
    private ?SimpleDataToken $loginAnonToken;
    private bool $facebook;
    private bool $google;
    private bool $microsoft;
    private bool $twitter;
    private bool $reddit;

    public function __construct(
        ?SimpleDataToken $loginAnonToken = null,
        bool $facebook = false,
        bool $google = false,
        bool $microsoft = false,
        bool $twitter = false,
        bool $reddit = false
    ) {
        $this->loginAnonToken = $loginAnonToken;
        $this->facebook = $facebook;
        $this->google = $google;
        $this->microsoft = $microsoft;
        $this->twitter = $twitter;
        $this->reddit = $reddit;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [];
        if ($this->loginAnonToken) {
            $data['anon'] = [
                'path' => '/login/anonymous',
                'token' => (string)$this->loginAnonToken,
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
        if ($this->reddit) {
            $data['reddit'] = '/login/reddit';
        }

        return $data;
    }
}
