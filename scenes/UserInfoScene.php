<?php

namespace scenes;

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
     * @param Telegram $ctx
     * @return void
     * @throws GuzzleException
     */
    public function __invoke(Telegram $ctx): void
    {
        $ctx->answer(<<<TEXT
Hi {$ctx->getFirstName()}!
Enter your name
TEXT);

        if (!$this->isStarted()) {
            $ctx->redis->set($this->sceneKey, $this->step);
        }
    }



    public function handle(callable $cb)
    {
        $this->steps[] = $cb;
    }


    public function runHandlers()
    {
        if (!$this->isStarted()) {
            return;
        }

        foreach ($this->steps as $index => $stepCb) {
            $step = $this->ctx->redis->get($this->sceneKey);
            if ((int)$step === $index) {
                $stepCb($this->ctx);
                $this->ctx->redis->set($this->sceneKey, $step + 1);
                break;
            }
        }
    }


    public function isStarted(): bool
    {
        return $this->ctx->redis->exists($this->sceneKey);
    }
}