<?php

namespace app\core;

use app\core\interfaces\CacheInterface;
use Predis\Client as RedisClient;

class RedisCache implements CacheInterface
{
    public RedisClient $redis;

    public function __construct(array $config)
    {
        $this->redis = new RedisClient($config);
        $this->redis->connect();
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->redis->get($key) ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->redis->set($key, $value);
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function deleteByRegexp(string $exp): void
    {
        $sceneKeys = $this->redis->keys($exp);
        foreach ($sceneKeys as $sceneKey) {
            $this->redis->del($sceneKey);
        }
    }

    public function exists(string $key): bool
    {
        return $this->redis->exists($key);
    }

    public function flush(): void
    {
        $this->redis->flushall();
    }

    public function getKeysByRegexp(string $exp): array
    {
        return $this->redis->keys($exp);
    }
}