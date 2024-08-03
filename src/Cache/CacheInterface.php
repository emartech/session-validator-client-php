<?php

namespace SessionValidator\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): bool;
}
