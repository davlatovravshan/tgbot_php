<?php

namespace app\scenes;

use app\core\TgBot;

interface SceneInterface
{
    public function onStart(): void;
    public function onFinish(TgBot $ctx): void;
    public function initSteps(): array;
}