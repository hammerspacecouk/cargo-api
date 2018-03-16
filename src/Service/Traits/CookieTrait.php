<?php
declare(strict_types=1);

namespace App\Service\Traits;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

trait CookieTrait
{
    private function makeCookie(string $content, string $name, ?DateTimeImmutable $expire): Cookie
    {
        if (!$expire) {
            $expire = 0; // session cookie
        }

        return new Cookie(
            $name,
            $content,
            $expire,
            '/',
            $this->applicationConfig->getCookieScope(),
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }
}
