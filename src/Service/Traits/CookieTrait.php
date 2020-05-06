<?php
declare(strict_types=1);

namespace App\Service\Traits;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

trait CookieTrait
{
    private function makeCookie(
        string $content,
        string $name,
        ?DateTimeImmutable $expire,
        ?string $sameSite = 'lax'
    ): Cookie {
        $expireValue = 0; // session cookie
        if ($expire) {
            $expireValue = $expire;
        }
        $secure = true;
        if ($this->applicationConfig->getCookieScope() === 'localhost') {
            $secure = false; // todo - localhost on https so this isn't needed
        }

        return new Cookie(
            $name,
            $content,
            $expireValue,
            '/',
            $this->applicationConfig->getCookieScope(),
            $secure, // secureCookie
            true, // httpOnly,
            false,
            $sameSite
        );
    }
}
