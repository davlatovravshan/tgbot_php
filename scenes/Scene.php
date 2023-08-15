<?php

namespace app\scenes;


use app\core\scenes\SceneStep;
use app\core\TgBot;
use app\core\TgHelper;
use Exception;


class Scene implements SceneInterface
{
    use SceneTrait;

    /**
     * @var array SceneStep[]
     */
    private array $steps;

    protected string $sceneName;

    public function __construct(protected TgBot $ctx)
    {
        $this->steps = $this->initSteps();
    }

    public function runSteps(): void
    {
        switch (true) {
            case $this->isCancel():
                $this->finish(true);
                break;
            case $this->isBack():
                $this->back(true);
                break;
            case $this->isNext():
                $this->next(true);
                break;
            default:
                $this->getCurrentStep()->handle();
        }
    }

    public function start(): void
    {
        $this->onStart();
        $this->next();
    }

    public function finish($removeLastMsg = false): void
    {
        $this->ctx->answerCbQuery();
        if ($removeLastMsg) {
            $this->ctx->deleteLastMessage();
        }

        $this->clearCache();
        $this->onFinish($this->ctx);
    }


    public function back($removeLastMsg = false): void
    {
        $stepIndex = $this->getCurrentStepIndex() - 1;
        $this->toStep($stepIndex, $removeLastMsg);
    }

    public function next($removeLastMsg = false): void
    {
        $stepIndex = $this->getCurrentStepIndex();
        $stepIndex = $stepIndex !== null ? ($stepIndex + 1) : 0;
        $this->toStep($stepIndex, $removeLastMsg);
    }


    public function toStep(int $stepIndex, $removeLastMsg = false): void
    {
        $this->ctx->answerCbQuery();
        if ($removeLastMsg) {
            $this->ctx->deleteLastMessage();
        }

        $stepName = array_keys($this->steps)[$stepIndex];
        $step = $this->steps[$stepName] ?? null;

        if ($step) {
            $this->setCache($stepName);
            $step->start();
        } else {
            $this->finish();
        }
    }

    protected function getSceneKey(): string
    {
        return "scene_{$this->sceneName}_{$this->ctx->getFromId()}";
    }

    protected function getSceneDataKey(): string
    {
        return $this->getSceneKey() . '_data';
    }

    protected function appendData(array $array): void
    {
        try {
            $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
            $cacheData = $cacheData ? json_decode($cacheData, true) : [];

            $newData = array_merge($cacheData, $array);

            $this->ctx->getCache()->set($this->getSceneDataKey(), json_encode($newData));
        } catch (Exception $e) {
            TgHelper::console('Error while append data: ' . $e->getMessage());
        }
    }

    protected function getData(): array
    {
        try {
            $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
            return $cacheData ? json_decode($cacheData, true) : [];
        } catch (Exception $e) {
            TgHelper::console('Error while get data: ' . $e->getMessage());
            return [];
        }
    }

    private function setCache(string $value): void
    {
        try {
            $this->ctx->getCache()->set($this->getSceneKey(), $value);
        } catch (Exception $e) {
            TgHelper::console('Error while set cache: ' . $e->getMessage());
        }
    }

    private function clearCache(): void
    {
        try {
            $this->ctx->getCache()->delete($this->getSceneKey());
            $this->ctx->getCache()->delete($this->getSceneDataKey());
        } catch (Exception $e) {
            TgHelper::console('Error while clear cache: ' . $e->getMessage());
        }
    }

    private function getCurrentStepIndex(): ?int
    {
        $stepName = $this->getCurrentStepName();
        if (!empty($stepName)) {
            return array_search($stepName, array_keys($this->steps));
        }
        return null;
    }

    private function getCurrentStepName(): ?string
    {
        try {
            return $this->ctx->getCache()->get($this->getSceneKey());
        } catch (Exception $e) {
            TgHelper::console($e->getMessage());
            return null;
        }
    }

    private function getCurrentStep(): ?SceneStep
    {
        $stepName = $this->getCurrentStepName();
        return $this->steps[$stepName] ?? null;
    }

    private function isCancel(): bool
    {
        return $this->isActionEquals(SceneCbEnum::CANCEL);
    }

    private function isBack(): bool
    {
        return $this->isActionEquals(SceneCbEnum::BACK);
    }

    private function isNext(): bool
    {
        return $this->isActionEquals(SceneCbEnum::NEXT);
    }

    private function isActionEquals(SceneCbEnum $cbEnum): bool
    {
        return $this->ctx->isCbEquals($cbEnum->value) || $this->ctx->isCommandEquals($cbEnum->value);
    }

    public function onStart(): void
    {
        // TODO: Implement onStart() method.
    }

    public function onFinish(TgBot $ctx): void
    {
        // TODO: Implement onFinish() method.
    }

    public function initSteps(): array
    {
        return [];
    }
}