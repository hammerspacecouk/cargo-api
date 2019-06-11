<?php
declare(strict_types=1);

namespace App\Infrastructure;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    private const CACHE_HIT_KEY = 'HIT';
    private const CACHE_MISS_KEY = 'MISS';

    private $adapter;
    private $logger;
    private $applicationConfig;

    public function __construct(
        CacheInterface $installedCacheAdapter,
        ApplicationConfig $applicationConfig,
        LoggerInterface $logger
    ) {
        $this->adapter = $installedCacheAdapter;
        $this->logger = $logger;
        $this->applicationConfig = $applicationConfig;
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

    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Caching ' . $key);
        return $this->adapter->set($key, $value, $ttl);
    }

    public function delete($key): bool
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Deleting cache key ' . $key);
        return $this->adapter->delete($key);
    }

    public function has($key): bool
    {
        $key = $this->sanitiseKey($key);
        $this->logger->debug('Checking cache key ' . $key);
        return $this->adapter->has($key);
    }

    public function clear(): bool
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

    /**
     * The PSR standard only requires certain characters and reserves others. It also has a maximum length
     * https://www.php-fig.org/psr/psr-16/#12-definitions
     * Trim and Replace them with safe characters, and concatenate with hash to reduce risk of collisions after the edit
     * @param $key
     * @return string
     */
    private function sanitiseKey($key): string
    {
        $key = trim($key);

        // add the application version so that new code doesn't talk to old caches
        $key .= $this->applicationConfig->getVersion();

        // find a hash of the whole original string
        $keyHash = '_' . substr(sha1($key), 0, 8);

        // remove any characters not defined in the PSR standard
        $key = preg_replace('/[^A-Za-z0-9_\.]/','_', $key);

        // limit length to meet standard (leave space for the hash we're about to concatenate)
        $key = substr($key, 0, 64 - strlen($keyHash));

        return $key . $keyHash;
    }
}
