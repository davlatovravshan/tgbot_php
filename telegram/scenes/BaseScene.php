<?php

namespace telegram\scenes;

use Exception;
use telegram\TgBot_old;
use telegram\TgBot;

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
     * @var TgBot_old
     */
    public $ctx;


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
     *
     */
    public const CANCEL_BUTTON = [
        [
            [
                'text' => 'Отмена',
                'callback_data' => 'cancel',
            ],
        ],
    ];


    /**
     * @param string $sceneName
     * @param TgBot $ctx
     * @throws Exception
     */
    public function __construct(string $sceneName, TgBot $ctx)
    {
        $this->chatId = $ctx->getFromId();
        $this->ctx = $ctx;
        $this->sceneKey = self::getSceneKey($sceneName, $ctx->getFromId());
        $this->sceneDataKey = self::getSceneKey($sceneName, $ctx->getFromId(), true);

        $this->initHandlers();
        $this->runHandlers();
    }


    /**
     * @param string $scene
     * @param $chatId
     * @param bool $isData
     * @return string
     */
    public static function getSceneKey(string $scene, $chatId, bool $isData = false): string
    {
        $sceneName = "scene_{$scene}_{$chatId}";
        if ($isData) {
            return "{$sceneName}_data";
        }
        return $sceneName;
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
        try {
            $step = $this->ctx->getRedis()->get($this->sceneKey);
            console("runHandlers sceneKey: " . $this->sceneKey);
            console("runHandlers step: " . $step);

            if (empty($this->steps) || (empty($step) && $step != 0)) {
                return;
            }

            $stepCb = get($this->steps, $step);
            if ($stepCb) {
                $stepCb($this->ctx);
            }
        } catch (Exception $e) {
            console($e->getMessage());
            $this->finish();
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
     * @param string|null $value
     * @return void
     * @throws Exception
     */
    public function appendData(string $key, string $value = null): void
    {
        $data = $this->ctx->getRedis()->get($this->sceneDataKey);
        $data = json_decode($data, true);
        $data[$key] = $value ?? '';
        $this->ctx->getRedis()->set($this->sceneDataKey, json_encode($data));
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getData(string $key = null)
    {
        $data = $this->ctx->getRedis()->get($this->sceneDataKey);
        $data = json_decode($data, true);
        if ($key) {
            return get($data, $key);
        }
        return $data;
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
     * @param string|null $key
     * @return bool
     * @throws Exception
     */
    public function isStarted(string $key = null): bool
    {
        if ($key) {
            return $this->ctx->getRedis()->exists($key);
        }
        return $this->ctx->getRedis()->exists($this->sceneKey);
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
        $anotherScenes = $this->ctx->getRedis()->keys("scene_*_{$this->ctx->getFromId()}");
        console("Has another scene: " . json_encode($anotherScenes));
        if (!empty($anotherScenes)) {
            return;
        }
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