<?php

namespace SessionValidator\Cache;

class ApcCache implements CacheInterface
{
    /** @var int */
    private $ttl;

    public function __construct($ttl)
    {
        $this->ttl = $ttl;
    }

    public function get($key)
    {
        return apcu_fetch($key);
    }

    public function set($key, $value)
    {
        return apcu_add($key, $value, $this->ttl);
    }
}
