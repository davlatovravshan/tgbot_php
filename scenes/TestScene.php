<?php

namespace app\scenes;


use app\core\scenes\SceneStep;
use app\core\TgBot;
use app\core\TgHelper;
use Exception;


class TestScene
{
    /**
     * @var array SceneStep[]
     */
    private array $steps;

    protected string $sceneName = 'test';

    public function getSceneKey(): string
    {
        return "scene_{$this->sceneName}_{$this->ctx->getFromId()}";
    }

    public function getSceneDataKey(): string
    {
        return $this->getSceneKey() . '_data';
    }


    public function __construct(protected TgBot $ctx)
    {
        $this->steps = [
            'name' => new SceneStep(
                function () {
                    $this->ctx->answer('What is your name?');
                },
                function () {
                    $this->ctx->answer("Your name: {$this->ctx->getText()}");
                    $this->appendData([
                        'name' => $this->ctx->getText(),
                    ]);
                    $this->next();
                }
            ),

            'phone' => new SceneStep(
                function () {
                    $this->ctx->answer('What is your phone?');
                },
                function () {
                    $this->ctx->answer("Your phone: {$this->ctx->getText()}");
                    $this->appendData([
                        'phone' => $this->ctx->getText(),
                    ]);
                    $this->next();
                }
            ),

            'age' => new SceneStep(
                function () {
                    $this->ctx->answer('How old are you?');
                },
                function () {
                    $this->ctx->answer("Your age: {$this->ctx->getText()}");
                    $this->appendData([
                        'age' => $this->ctx->getText(),
                    ]);

                    $data = json_encode($this->getData());
                    $this->ctx->answer("Your data: {$data}");
                    $this->finish();
                }
            ),
        ];
    }


    /**
     * @throws Exception
     */
    public function start(): void
    {
        $this->ctx->answer('Welcome to TEST scene');
        $this->next();
    }


    public function runSteps(): void
    {
        $this->getCurrentStep()?->handle();
    }


    /**
     * @throws Exception
     */
    public function finish(): void
    {
        $this->ctx->getCache()->delete($this->getSceneKey());
        $this->ctx->getCache()->delete($this->getSceneDataKey());
        $this->ctx->answer('Bye');
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


    private function getNextStepName(): ?string
    {
        $stepIndex = $this->getCurrentStepIndex();
        TgHelper::console('$this->getCurrentStepIndex(): ' . $stepIndex);
        $stepIndex = $stepIndex !== null ? $stepIndex + 1 : 0;
        return TgHelper::get(array_keys($this->steps), $stepIndex);
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

    /**
     * @throws Exception
     */
    private function appendData(array $array): void
    {
        $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
        $cacheData = $cacheData ? json_decode($cacheData, true) : [];

        $newData = array_merge($cacheData, $array);

        $this->ctx->getCache()->set($this->getSceneDataKey(), json_encode($newData));
    }

    /**
     * @throws Exception
     */
    private function getData(): array
    {
        $cacheData = $this->ctx->getCache()->get($this->getSceneDataKey());
        return $cacheData ? json_decode($cacheData, true) : [];
    }

}