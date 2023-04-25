<?php

namespace app\core\interfaces;

interface CacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, string $value): void;

    public function delete(string $key): void;

    public function deleteByRegexp(string $exp): void;

    public function getKeysByRegexp(string $exp): array;

    public function exists(string $key): bool;

    public function flush(): void;
}