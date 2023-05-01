<?php

namespace app\scenes;


use app\core\scenes\SceneStep;
use app\core\TgBot;
use app\core\TgHelper;
use Exception;


class Scene implements SceneInterface
{
    use SceneTrait;

    public const BACK_COMMAND = 'back';
    public const NEXT_COMMAND = 'next';
    public const CANCEL_COMMAND = 'cancel';

    /**
     * @var array SceneStep[]
     */
    private array $steps;

    protected string $sceneName = 'test';

    public function __construct(protected TgBot $ctx)
    {
        $this->steps = $this->initSteps();
    }

    protected function getSceneKey(): string
    {
        return "scene_{$this->sceneName}_{$this->ctx->getFromId()}";
    }

    protected function getSceneDataKey(): string
    {
        return $this->getSceneKey() . '_data';
    }

    /**
     * @throws Exception
     */
    protected function appendData(array $array): void
    {
        $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
        $cacheData = $cacheData ? json_decode($cacheData, true) : [];

        $newData = array_merge($cacheData, $array);

        $this->ctx->getCache()->set($this->getSceneDataKey(), json_encode($newData));
    }

    /**
     * @throws Exception
     */
    protected function getData(): array
    {
        $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
        return $cacheData ? json_decode($cacheData, true) : [];
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        $this->onStart();
        $this->next();
    }

    /**
     * @throws Exception
     */
    public function finish(): void
    {
        $this->ctx->getCache()->delete($this->getSceneKey());
        $this->ctx->getCache()->delete($this->getSceneDataKey());
        $this->onFinish($this->ctx);
    }

    /**
     * @throws Exception
     */
    public function runSteps(): void
    {
        if ($this->isCancel()) {
            $this->finish();
        } else if ($this->isBack()) {
            $this->back();
        } else if ($this->isNext()) {
            $this->next();
        } else {
            $this->getCurrentStep()?->handle();
        }
    }

    /**
     * @throws Exception
     */
    public function back(): void
    {
        $stepIndex = $this->getCurrentStepIndex();
        $prevStepIndex = TgHelper::get(array_keys($this->steps), $stepIndex - 1);
        $prevStep = $this->steps[$prevStepIndex] ?? null;

        if ($prevStep) {
            $this->ctx->getCache()->set($this->getSceneKey(), $prevStepIndex);
            $prevStep->start();
        } else {
            $this->ctx->getCache()->delete($this->getSceneKey());
        }
    }

    /**
     * @throws Exception
     */
    public function next(): void
    {
        $nextStepName = $this->getNextStepName();
        $nextStep = $this->steps[$nextStepName];

        if ($nextStep) {
            TgHelper::console('next() ' . $nextStepName);
            $this->ctx->getCache()->set($this->getSceneKey(), $nextStepName);
            $nextStep->start();
        } else {
            $this->ctx->getCache()->delete($this->getSceneKey());
        }
    }

    protected function getCommonMarkup(bool $isBack, bool $isNext, bool $isCancel = true): array
    {
        $firstKeyboardRow = [];
        if ($isBack) {
            $firstKeyboardRow[] = SceneCbEnum::BACK;
        }
        if ($isNext) {
            $firstKeyboardRow[] = SceneCbEnum::NEXT;
        }

        return [
            'reply_markup' => [
                'inline_keyboard' => [
                    $this->getActionButtons(...$firstKeyboardRow),
                    $this->getActionButtons(SceneCbEnum::CANCEL)
                ]
            ],
        ];
    }

    protected function getActionButtons(SceneCbEnum ...$buttons): array
    {
        $result = [];
        foreach ($buttons as $button) {
            $result[] = $this->getCbBtn($button->getText(), $button);
        }
        return $result;
    }

    protected function getBackButton(string $text = 'Назад'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::BACK);
    }

    protected function getCancelButton(string $text = 'Отмена'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::CANCEL);
    }

    protected function getNextButton(string $text = 'Далее'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::NEXT);
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