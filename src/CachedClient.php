<?php

namespace SessionValidator;

use SessionValidator\Cache\ApcCache;
use SessionValidator\Cache\CacheInterface;

class CachedClient implements ClientInterface
{
    const CACHE_TTL = 300;

    /** @var Client */
    private $client;
    /** @var CacheInterface */
    private $cache;

    public static function create(ClientInterface $client)
    {
        return new self($client, new ApcCache(self::CACHE_TTL));
    }

    public function __construct(ClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function isValid($msid)
    {
        $cachedResult = $this->cache->get($msid);
        if ($cachedResult) {
            return $cachedResult;
        }

        $result = $this->client->isValid($msid);
        $this->cache->set($msid, $result);

        return $result;
    }

    public function filterInvalid(array $msids)
    {
        $result = $this->client->filterInvalid($msids);

        foreach ($msids as $msid) {
            if (!in_array($msid, $result)) {
                $this->cache->set($msid, true);
            }
        }

        return $result;
    }
}
