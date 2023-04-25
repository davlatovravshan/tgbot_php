<?php

namespace app\core\scenes;

use Closure;

class SceneStep
{
    public function __construct(
        private readonly Closure $startCb,
        private readonly Closure $handler
    ) {
    }

    public function start(): void
    {
        $cb = $this->startCb;
        $cb();
    }

    public function handle(): void
    {
        $cb = $this->handler;
        $cb();
    }
}