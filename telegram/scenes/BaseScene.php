<?php

namespace telegram\scenes;

use Exception;
use telegram\Telegram;

/**
 * Class BaseScene
 */
class BaseScene
{

    /**
     * @var int
     */
    public int $chatId;


    /**
     * @var Telegram
     */
    public Telegram $ctx;


    /**
     * @var string
     */
    public string $sceneName = '';


    /**
     * @var string
     */
    public string $sceneKey;


    /**
     * @var string
     */
    public string $sceneDataKey;


    /**
     * @var array
     */
    public array $steps = [];


    /**
     * @param Telegram $ctx
     * @throws Exception
     */
    public function __construct(Telegram $ctx)
    {
        $this->chatId = $ctx->getFromId();
        $this->ctx = $ctx;
        $this->sceneKey = "scene_{$this->sceneName}_{$ctx->getFromId()}";
        $this->sceneDataKey = "scene_{$this->sceneName}_{$ctx->getFromId()}_data";

        if ($this->isStarted()) {
            $this->initHandlers();
            $this->runHandlers();
        }
    }


    /**
     * @return void
     */
    public function initHandlers(): void
    {
    }


    /**
     * @param callable $cb
     * @return void
     */
    public function handle(callable $cb): void
    {
        $this->steps[] = $cb;
    }


    /**
     * @return void
     * @throws Exception
     */
    public function runHandlers(): void
    {
        if (empty($this->steps)) {
            return;
        }

        $step = $this->ctx->getRedis()->get($this->sceneKey);
        $stepCb = $this->steps[$step];
        if ($stepCb) {
            $stepCb($this->ctx, $this);
            if ($step == count($this->steps) - 1) {
                $this->finish();
            }
        }
    }


    /**
     * @return void
     * @throws Exception
     */
    public function next(): void
    {
        $step = $this->ctx->getRedis()->get($this->sceneKey);
        $this->ctx->getRedis()->set($this->sceneKey, $step + 1);
    }


    /**
     * @param string $key
     * @param string $value
     * @return void
     * @throws Exception
     */
    public function appendData(string $key, string $value): void
    {
        $data = $this->ctx->getRedis()->get($this->sceneDataKey);
        $data = json_decode($data, true);
        $data[$key] = $value;
        $this->ctx->getRedis()->set($this->sceneDataKey, json_encode($data));
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getData(): mixed
    {
        $data = $this->ctx->getRedis()->get($this->sceneDataKey);
        return json_decode($data, true);
    }


    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function setData(array $data): void
    {
        $this->ctx->getRedis()->set($this->sceneDataKey, json_encode($data));
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function isStarted(): bool
    {
        return $this->ctx->getRedis()->exists($this->sceneKey);
    }


    /**
     * @return void
     * @throws Exception
     */
    public function restart(): void
    {
        $this->finish();
        $this->start();
    }


    /**
     * @return void
     * @throws Exception
     */
    public function finish(): void
    {
        $this->ctx->getRedis()->del($this->sceneKey);
        $this->ctx->getRedis()->del($this->sceneDataKey);
        $this->steps = [];
    }


    /**
     * @return void
     * @throws Exception
     */
    public function start(): void
    {
        $this->ctx->getRedis()->set($this->sceneKey, 0);
        $this->onStart();
    }


    /**
     * @return void
     */
    public function onStart(): void
    {
    }

}