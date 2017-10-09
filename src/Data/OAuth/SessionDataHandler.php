<?php
declare(strict_types=1);

namespace App\Data\OAuth;

use App\Data\TokenHandler;
use App\Domain\Exception\InvalidTokenException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class SessionDataHandler
{
    private const COOKIE_NAME = 'SESSION_DATA_STORE';

    private $tokenHandler;
    private $data = [];

    public function __construct(TokenHandler $tokenHandler)
    {
        $this->tokenHandler = $tokenHandler;
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    public function makeCookie(): Cookie
    {
        $data = serialize($this->data);
        $claims = [
            'data' => $data,
        ];

        return new Cookie(
            self::COOKIE_NAME,
            (string)$this->tokenHandler->makeToken($claims),
            0,
            '/',
            null,
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }

    public function setFromRequest(Request $request): void
    {
        $cookie = $request->cookies->get(self::COOKIE_NAME);
        if (!$cookie) {
            throw new InvalidTokenException('No token found');
        }

        $token = $this->tokenHandler->parseTokenFromString($cookie, null);

        $this->data = unserialize($token->getClaim('data'));
    }
}
