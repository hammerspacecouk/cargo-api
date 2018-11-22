<?php
declare(strict_types=1);

namespace App\Infrastructure;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;

class Cache implements CacheInterface
{
    private const CACHE_HIT_KEY = 'HIT';
    private const CACHE_MISS_KEY = 'MISS';

    private $adapter;
    private $logger;

    public function __construct(
        SimpleCacheAdapter $adapter,
        LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    public function get($key, $default = null)
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Fetching cache for ' . $key);
        $value = $this->adapter->get($key, $default);
        $state = self::CACHE_MISS_KEY;
        if ($value !== $default) {
            $state = self::CACHE_HIT_KEY;
        }
        $this->logger->notice('[CACHE] [' . $state . '] [' . $key . ']');
        return $value;
    }

    public function set($key, $value, $ttl = null)
    {
        $key = $this->sanitiseKey($key);

        $this->logger->debug('Caching ' . $key . ' for ' . $ttl . ' seconds');
        return $this->adapter->set($key, $value, $ttl);
    }

    public function delete($key)
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Deleting cache key ' . $key);
        return $this->adapter->delete($key);
    }

    public function has($key)
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Checking cache key ' . $key);
        return $this->adapter->has($key);
    }

    public function clear()
    {
        $this->logger->debug('Clearing cache');
        return $this->adapter->clear();
    }

    public function getMultiple($keys, $default = null)
    {
        throw new BadMethodCallException(__METHOD__ . ' is not supported in this implementation');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new BadMethodCallException(__METHOD__ . ' is not supported in this implementation');
    }

    public function deleteMultiple($keys)
    {
        throw new BadMethodCallException(__METHOD__ . ' is not supported in this implementation');
    }

    private function sanitiseKey($key)
    {
        if (preg_match('/[' . preg_quote('{}()/\@:', '/') . ']/', $key)) {
            $key = sha1($key);
        }
        return $key;
    }
}
