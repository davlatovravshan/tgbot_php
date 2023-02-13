<?php

require_once 'autoload.php';

use GuzzleHttp\Exception\GuzzleException;
use middlewares\CheckAdminMiddleware;
use telegram\Telegram;
use scenes\UserInfoScene;


try {
    $telegram = new Telegram(BOT_TOKEN);
    $telegram->initRedis();

    $telegram->onCommand('start', function (Telegram $ctx) {
        $ctx->answer('Hi start', [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Click me', 'callback_data' => 'click'],
                    ],
                ]
            ]
        ]);
    });


    $telegram->onCommand('help', function (Telegram $ctx) {
        $ctx->answer('Hi help');
    });


    $telegram->onCallbackQuery('click', function (Telegram $ctx) {
        $ctx->answerCbQueryWithText('Hi click');
    });


    $telegram->onCommand('admin', new CheckAdminMiddleware, function (Telegram $ctx) {
        $ctx->answer('Hi admin');
    });


    /*$infoScene = new UserInfoScene($telegram);

    $infoScene->handle(function (Telegram $ctx) {
        $ctx->answer('Enter your age');
    });

    $infoScene->handle(function (Telegram $ctx) {
        $ctx->answer('Enter your sex');
    });

    $infoScene->runHandlers();

    $telegram->onCommand('info', $infoScene);*/



    $telegram->onMessage(function (Telegram $ctx) {
        $key = "prevMessage_{$ctx->getFromId()}";
        $prevMessage = $ctx->redis->get($key);

        $ctx->answer($ctx->getText() . '-' . $prevMessage);
        $ctx->redis->set($key, $ctx->getText());
    });

} catch (GuzzleException $e) {
    echo $e->getMessage();
}


//$telegram->redis->flushall();

// put redis info to db/redis.json
$redisKeys = $telegram->redis->keys('*');
$redisData = [];
foreach ($redisKeys as $key) {
    $redisData[$key] = $telegram->redis->get($key);
}
file_put_contents(
    'db/redis.json',
    json_encode($redisData) . PHP_EOL . PHP_EOL
);