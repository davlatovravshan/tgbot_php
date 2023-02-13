<?php

require_once 'autoload.php';

use GuzzleHttp\Exception\GuzzleException;
use middlewares\CheckAdminMiddleware;
use scenes\PhotoScene;
use telegram\Telegram;
use scenes\UserInfoScene;


try {
    $telegram = new Telegram(BOT_TOKEN);

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


    $infoScene = new UserInfoScene($telegram);
    $telegram->onCommand('info', fn() => $infoScene->start());

    $photoScene = new PhotoScene($telegram);
    $telegram->onCommand('photo', fn() => $photoScene->start());


    $telegram->onMessage(function (Telegram $ctx) {
        $key = "prevMessage_{$ctx->getFromId()}";
        $prevMessage = $ctx->getRedis()->get($key);

        $ctx->answer($ctx->getText() . '-' . $prevMessage);
        $ctx->getRedis()->set($key, $ctx->getText());
    });

} catch (GuzzleException $e) {
    echo $e->getMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}


//$telegram->getRedis()->flushall();

// put redis info to db/redis.json
$redisKeys = $telegram->getRedis()->keys('*');
$redisData = [];
foreach ($redisKeys as $key) {
    $redisData[$key] = $telegram->getRedis()->get($key);
}
file_put_contents(
    'db/redis.json',
    json_encode($redisData) . PHP_EOL . PHP_EOL
);