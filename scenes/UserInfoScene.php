<?php

namespace scenes;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use telegram\scenes\BaseScene;
use telegram\TgBot;


class UserInfoScene extends BaseScene
{

    /**
     * @throws GuzzleException
     */
    public function onStart(): void
    {
        $this->ctx->answer("Hi {$this->ctx->getFirstName()}!\nEnter your name");
    }


    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function initHandlers(): void
    {
        if ($this->ctx->isCommandEquals('cancel')) {
            $this->onCancel();
            return;
        }

        if ($this->ctx->isCbEquals('cancel')) {
            $this->onCancel();
            return;
        }

        $this->handle(function (TgBot $ctx) {
            $this->appendData('name', $ctx->getText());
            $ctx->answer('Enter your age');
            $this->next();
        });

        $this->handle(function (TgBot $ctx) {

            if (!is_numeric($ctx->getText())) {
                $ctx->answer('Age must be numeric');
                return;
            }

            $this->appendData('age', $ctx->getText());
            $ctx->answer('Enter your sex');
            $this->next();
        });

        $this->handle(function (TgBot $ctx) {
            $this->appendData('sex', $ctx->getText());
            $ctx->answer('Your info: ' . json_encode($this->getData()));
            $this->finish();
        });

    }


    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function onCancel(): void
    {
        $this->ctx->answer('Canceled');
        $this->finish();
    }

}