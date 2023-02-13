<?php

namespace telegram\scenes;

use telegram\Telegram;

class BaseScene
{

    public int $chatId;
    public Telegram $ctx;
    public string $sceneName = '';
    public string $sceneKey;
    public int $step = 0;
    public array $steps = [];


    public function __construct(Telegram $ctx)
    {
        $this->chatId = $ctx->getFromId();
        $this->ctx = $ctx;
        $this->sceneKey = "scene_{$this->sceneName}_{$ctx->getFromId()}";
    }



}