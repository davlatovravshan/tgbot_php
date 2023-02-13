<?php

namespace scenes;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use telegram\scenes\BaseScene;
use telegram\Telegram;


class UserInfoScene extends BaseScene
{

    public function __construct(Telegram $ctx)
    {
        $this->sceneName = 'info';
        parent::__construct($ctx);
    }

    /**
     * @throws Exception
     */
    public function initHandlers(): void
    {

        $this->handle(function (Telegram $ctx, UserInfoScene $scene) {
            $scene->appendData('name', $ctx->getText());
            $ctx->answer('Enter your age');
            $scene->next();
        });

        $this->handle(function (Telegram $ctx, UserInfoScene $scene) {

            if (!is_numeric($ctx->getText())) {
                $ctx->answer('Age must be numeric');
                return;
            }

            $scene->appendData('age', $ctx->getText());
            $ctx->answer('Enter your sex');
            $scene->next();
        });

        $this->handle(function (Telegram $ctx, UserInfoScene $scene) {
            $scene->appendData('sex', $ctx->getText());
            $ctx->answer('Your info: ' . json_encode($scene->getData()));
        });

        $scene = $this;

        $this->ctx->onCommand('cancel', function (Telegram $ctx) use ($scene) {
            $ctx->answer('Canceled');
            $scene->finish();
        });

        $this->ctx->onCommand('info', fn() => $scene->restart());
    }


    /**
     * @throws GuzzleException
     */
    public function onStart(): void
    {
        $this->ctx->answer("Hi {$this->ctx->getFirstName()}!\nEnter your name");
    }

}