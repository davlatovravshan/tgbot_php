<?php

require_once 'autoload.php';

use GuzzleHttp\Exception\GuzzleException;
use middlewares\CheckAdminMiddleware;
use scenes\PhotoScene;
use telegram\TgBot;
use scenes\UserInfoScene;


try {
    $telegram = new TgBot(BOT_TOKEN2, [
        'webhook' => true,
    ]);

    $telegram->registerScene('info', UserInfoScene::class);
    $telegram->registerScene('photo', PhotoScene::class);


    $telegram->onCommand('start', function (TgBot $ctx) {
        $ctx->answer('Hi start', [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Click me', 'callback_data' => 'click'],
                    ],
                ],
            ],
        ]);
    });

    $telegram->onCommand('help', function (TgBot $ctx) {
        $ctx->answer('Hi help');
    });

    $telegram->onCallbackQuery('click', function (TgBot $ctx) {
        $ctx->answerCbQuery();
        $ctx->answer('Hi click');
    });

    $telegram->onCommand('info', function (TgBot $ctx) {
        $ctx->answer('Hi info');
        $ctx->startScene('info');
    });

    $telegram->onCommand('photo', function (TgBot $ctx) {
        $ctx->answer('Hi photo');
        $ctx->startScene('photo');
    });

    $telegram->onCommand('admin', new CheckAdminMiddleware, function (TgBot $ctx) {
        $ctx->answer('Hi admin');
    });

    $telegram->on('text', function (TgBot $ctx) {
        $key = "prevMessage_{$ctx->getFromId()}";
        $prevMessage = $ctx->getRedis()->get($key);

        if (!empty($ctx->getText())) {
            $ctx->answer($ctx->getText() . '-' . $prevMessage);
            $ctx->getRedis()->set($key, $ctx->getText());
        }
    });

    // handler for any ignored callback queries
    $telegram->onAnyCallbackQuery(function (TgBot $ctx) {
        $ctx->answerCbQuery([
            'text' => '👌',
        ]);
    });

    $telegram->launch();
} catch (Exception $e) {
    console($e->getMessage());
} catch (GuzzleException $e) {
    console($e->getMessage());
}