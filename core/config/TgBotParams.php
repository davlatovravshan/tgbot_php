<?php

namespace app\core\config;

class TgBotParams
{

    private array $cacheParams;

    public function __construct(
        private readonly bool $webhook = false,
        array $cacheParams = [],
    ) {
        $this->cacheParams = array_merge_recursive([
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
            'password' => REDIS_PASSWORD,
        ], $cacheParams);
    }

    public function isWebhook(): bool
    {
        return $this->webhook;
    }

    public function getCacheParams(): array
    {
        return $this->cacheParams;
    }
}