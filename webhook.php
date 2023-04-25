<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use app\core\TgBot;
use GuzzleHttp\Exception\GuzzleException;


try {
    $telegram = new TgBot(BOT_TOKEN2, [
        'webhook' => true,
    ]);

    $telegram->registerScene('test', TestScene::class);

    $telegram->onCommand('start', function (TgBot $ctx) {
        $ctx->answer('Hi start', [
            'reply_markup' => [
                'inline_keyboard' => [[['text' => 'Click me', 'callback_data' => 'click']]],
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

    $telegram->onAnyCallbackQuery(function (TgBot $ctx) {
        $ctx->answerCbQuery([
            'text' => '👌',
        ]);
    });

    $telegram->launch();
} catch (GuzzleException|Exception $e) {
    console($e->getMessage());
}