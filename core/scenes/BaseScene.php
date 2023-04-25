<?php

namespace app\core\scenes;

use app\core\TgBot;
use app\core\TgHelper;
use Exception;

/**
 * Class BaseScene
 */
abstract class BaseScene
{

    public TgBot $ctx;


    private string $sceneKey;


    private string $sceneDataKey;


    private array $steps = [];


    public const CANCEL_BUTTON = [
        [
            [
                'text' => 'Отмена',
                'callback_data' => 'cancel',
            ],
        ],
    ];


    /**
     * @throws Exception
     */
    public function __construct(string $sceneName, TgBot $ctx)
    {
        $this->ctx = $ctx;
        $this->sceneKey = self::getSceneKey($sceneName, $ctx->getFromId());
        $this->sceneDataKey = self::getSceneKey($sceneName, $ctx->getFromId(), true);

        if ($this->ctx->isPrivateChat()) {
            $this->initHandlers();
            $this->runHandlers();
        }
    }


    public static function getSceneKey(string $scene, $chatId, bool $isData = false): string
    {
        $sceneName = "scene_{$scene}_$chatId";
        if ($isData) {
            return "{$sceneName}_data";
        }
        return $sceneName;
    }


    protected function handle(callable $cb): void
    {
        $this->steps[] = $cb;
    }


    private function runHandlers(): void
    {
        try {
            $step = $this->ctx->getCache()->get($this->sceneKey);
            TgHelper::console("runHandlers sceneKey: " . $this->sceneKey);
            TgHelper::console("runHandlers step: " . $step);

            if (empty($this->steps) || (empty($step) && $step != 0)) {
                return;
            }

            $stepCb = get($this->steps, $step);
            if ($stepCb) {
                $stepCb($this->ctx);
            }

            if ($step < 0) {
                $this->onStart();
                $this->next();
            }
        } catch (Exception $e) {
            TgHelper::console($e->getMessage());
            $this->finish();
        }
    }


    /**
     * @throws Exception
     */
    protected function next(): void
    {
        $step = $this->ctx->getCache()->get($this->sceneKey);
        $this->ctx->getCache()->set($this->sceneKey, $step + 1);
    }


    /**
     * @throws Exception
     */
    protected function back(): void
    {
        $step = $this->ctx->getCache()->get($this->sceneKey);
        $this->ctx->getCache()->set($this->sceneKey, $step - 2);
    }


    /**
     * @throws Exception
     */
    protected function appendData(string $key, string $value = null): void
    {
        if ($this->ctx->isCbEquals('back') || $this->ctx->isCommandEquals('back')) {
            return;
        }
        $data = $this->ctx->getCache()->get($this->sceneDataKey);
        $data = json_decode($data, true);
        $data[$key] = $value ?? '';
        $this->ctx->getCache()->set($this->sceneDataKey, json_encode($data));
    }


    /**
     * @throws Exception
     */
    protected function getData(string $key = null)
    {
        $data = $this->ctx->getCache()->get($this->sceneDataKey);
        $data = json_decode($data, true);
        if ($key) {
            return TgHelper::get($data, $key);
        }
        return $data;
    }


    /**
     * @throws Exception
     */
    protected function setData(array $data): void
    {
        $this->ctx->getCache()->set($this->sceneDataKey, json_encode($data));
    }


    /**
     * @throws Exception
     */
    public function isStarted(string $key = null): bool
    {
        if ($key) {
            return $this->ctx->getCache()->exists($key);
        }
        return $this->ctx->getCache()->exists($this->sceneKey);
    }


    /**
     * @throws Exception
     */
    protected function finish(): void
    {
        $this->ctx->resetAllScenes();
        $this->steps = [];
    }


    /**
     * @throws Exception
     */
    public function start(): void
    {
        $anotherScenes = $this->ctx->getCache()->getKeysByRegexp("scene_*_{$this->ctx->getFromId()}");
        TgHelper::console("Has another scene: " . json_encode($anotherScenes));
        if (!empty($anotherScenes)) {
            return;
        }
        $this->ctx->getCache()->set($this->sceneKey, 0);
        $this->onStart();
    }

    abstract public function onStart(): void;

    abstract public function initHandlers(): void;

}