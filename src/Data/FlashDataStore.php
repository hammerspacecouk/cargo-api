<?php
declare(strict_types=1);

namespace App\Data;

use App\Domain\Exception\TokenException;
use App\Infrastructure\ApplicationConfig;
use App\Domain\ValueObject\Message\Message;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class FlashDataStore
{
    // todo. this should be different
    private const COOKIE_NAME = 'FLASH_DATA_STORE';

    private $tokenHandler;
    private $applicationConfig;
    private $data = [];
    private $messages = [];

    public function __construct(TokenProvider $tokenHandler, ApplicationConfig $applicationConfig)
    {
        $this->tokenHandler = $tokenHandler;
        $this->applicationConfig = $applicationConfig;
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    public function getOnce(string $key)
    {
        $value = $this->get($key);
        $this->clear($key);
        return $value;
    }

    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    public function clear(string $key): void
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    public function destroy(): void
    {
        $this->data = [];
        $this->messages = [];
    }

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /** @return Message[] */
    public function readMessages(): array
    {
        $messages = $this->messages;
        usort($messages, function (Message $a, Message $b) {
            return $b->getWeighting() <=> $a->getWeighting(); // https://youtu.be/7TYJyCCO8Dc?t=40s
        });

        $this->messages = []; // reset so its gone for next request
        return $messages;
    }

    public function makeCookie(): Cookie
    {
        $claims = [
            'data' => serialize($this->data),
            'messages' => serialize($this->messages),
        ];

        return new Cookie(
            self::COOKIE_NAME,
            (string)$this->tokenHandler->makeToken($claims, 'PT1H'),
            0,
            '/',
            $this->applicationConfig->getCookieScope(),
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }

    public function setFromRequest(Request $request): void
    {
        $cookie = $request->cookies->get(self::COOKIE_NAME);
        if (!$cookie) {
            return;
        }

        try {
            $token = $this->tokenHandler->parseTokenFromString($cookie);

            $this->data = unserialize($token->getClaim('data'));
            $this->messages = unserialize($token->getClaim('messages'));
        } catch (TokenException $exception) {
            // if there any kind of exceptions with the token,
            // just ignore it as though there was no token
        }
    }
}
