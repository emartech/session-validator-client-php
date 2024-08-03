<?php

namespace SessionValidator\Cache;

class ApcCache implements CacheInterface
{
    private int $ttl;

    public function __construct(int $ttl)
    {
        $this->ttl = $ttl;
    }

    public function get(string $key): mixed
    {
        return apcu_fetch($key);
    }

    public function set(string $key, mixed $value): bool
    {
        return apcu_add($key, $value, $this->ttl);
    }
}
