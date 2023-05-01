<?php

namespace app\scenes;


use app\core\scenes\SceneStep;
use app\core\TgBot;
use app\core\TgHelper;


class TestScene extends Scene
{
    protected string $sceneName = 'test';

    public function onStart(): void
    {
        $this->ctx->answer('Welcome to TEST scene!!!');
    }

    public function onFinish(TgBot $ctx): void
    {
        $ctx->answer('Bye!!!');
    }

    public function initSteps(): array
    {
        return [
            'name' => new SceneStep(
                function () {
                    $this->ctx->answer(
                        'What is your name?',
                        $this->getCommonMarkup([
                            [SceneCbEnum::BACK, SceneCbEnum::NEXT],
                            [SceneCbEnum::CANCEL]
                        ])
                    );
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
                    $this->ctx->answer(
                        'What is your phone?',
                        $this->getCommonMarkup([
                            [SceneCbEnum::BACK, SceneCbEnum::NEXT],
                            [SceneCbEnum::CANCEL]
                        ])
                    );
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
                    $this->ctx->answer(
                        'How old are you?',
                        $this->getCommonMarkup([
                            [SceneCbEnum::BACK, SceneCbEnum::NEXT],
                            [SceneCbEnum::CANCEL]
                        ])
                    );
                },
                function () {
                    $this->ctx->answer("Your age: {$this->ctx->getText()}");
                    $this->appendData([
                        'age' => $this->ctx->getText(),
                    ]);

                    $data = json_encode($this->getData());
                    $this->ctx->answer("Your data: $data");
                    $this->finish();
                }
            ),
        ];
    }

}