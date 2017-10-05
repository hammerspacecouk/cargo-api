<?php
declare(strict_types=1);

namespace App\Data\Facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class PersistentDataHandler implements PersistentDataInterface
{
    private const COOKIE_NAME = 'FB_DATA_STORE';

    private $data = [];

    /**
     * Get a value from a persistent data store.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Set a value in the persistent data store.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function makeCookie()
    {
        return new Cookie(
            self::COOKIE_NAME,
            serialize($this->data),
            0,
            '/',
            null,
            false, // secureCookie - todo - be true as often as possible
            true // httpOnly
        );
    }

    public function setFromRequest(Request $request)
    {
        $this->data = unserialize($request->cookies->get(self::COOKIE_NAME));
    }
}
