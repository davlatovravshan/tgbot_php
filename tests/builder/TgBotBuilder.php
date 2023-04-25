<?php

namespace app\tests\builder;

use app\core\config\TgBotParams;
use app\core\TgBot;

class TgBotBuilder
{
    public function __construct(

    ) {

        $bot = new TgBot('123123123', new TgBotParams());

        $bot->onCommand('start', function (TgBot $ctx) {
            $ctx->answer('Hello');
        });

    }
}