<?php

namespace app\core;

use app\core\config\TgBotParams;
use app\core\interfaces\CacheInterface;
use app\core\interfaces\TelegramInterface;
use app\core\scenes\BaseScene;
use app\scenes\TestScene;
use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 *  Telegram bot class
 */
class TgBot implements TelegramInterface
{
    use TelegramTrait;

    private string $host;
    private string $apiUrl;
    private ?array $input;
    private array $middlewares = [];
    private array $handlers = [];
    private array $commandHandlers = [];
    private array $cbHandlers = [];
    private array $scenes = [];
    private array $sceneHandlers = [];
    public const ALLOWED_UPDATES = [
        'message',
        'edited_message',
        'channel_post',
        'edited_channel_post',
        'inline_query',
        'chosen_inline_result',
        'callback_query',
        'shipping_query',
        'pre_checkout_query',
        'poll',
        'poll_answer'
    ];
    private CacheInterface $cache;


    /**
     * @throws Exception
     */
    public function __construct(
        private readonly string $token,
        private readonly TgBotParams $options
    ) {
        $this->host = 'https://api.telegram.org/';
        $this->apiUrl = $this->host . "bot$this->token/";

        $this->initCache();
    }


    private function setWebhook(string $url, array $options = []): void
    {
        $options['url'] = $url;
        $this->sendRequest('setWebhook', $options);
    }


    /**
     * @throws Exception
     */
    private function initCache(): void
    {
        try {
            $this->cache = new RedisCache($this->options->getCacheParams());
        } catch (Exception $e) {
            TgHelper::console('Cannot connect to Cache client: ' . $e->getMessage());
        }
    }


    /**
     * @throws Exception
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }


    /**
     * @throws Exception
     */
    public function launch(): void
    {
        TgHelper::console($this->options->isWebhook());
        if (!$this->options->isWebhook()) {
            $this->resetAllScenes();
            $this->longPolling();
        } else {
            $this->loadInput();
            $this->runHandlers();
        }
    }


    public function resetAllScenes(): void
    {
        // Delete all scenes from cache
        $this->cache->deleteByRegexp(BaseScene::getSceneKey('*', "*"));

        // Delete all scene data from cache
        $this->cache->deleteByRegexp(BaseScene::getSceneKey('*', "*", true));
    }


    private function longPolling(): void
    {
        TgHelper::console('Long polling started');

        $offset = 0;
        while (true) {
            try {
                $update = $this->getUpdates($offset);
                if (empty($update)) {
                    continue;
                }

                $updateId = TgHelper::get($update, 'update_id');
                $offset = (int)$updateId + 1;

                TgHelper::console('Update received');
                TgHelper::console($update);

                $this->input = $update;
                $this->message = $this->getMessageObject();

                // Run all handlers
                $this->runHandlers();
            } catch (GuzzleException $e) {
                TgHelper::console($e->getMessage());
            } catch (Exception $e) {
                TgHelper::console($e->getMessage());
            }
        }
    }


    private function getUpdates(int $offset = 0): array
    {
        $params = [
            'offset' => $offset,
            'timeout' => 60,
            'allowed_updates' => self::ALLOWED_UPDATES
        ];
        $response = $this->sendRequest('getUpdates', $params);
        return TgHelper::get($response, 'result.0') ?? [];
    }


    private function loadInput(): void
    {
        $this->input = json_decode(file_get_contents('php://input'), true);
        $this->message = $this->getMessageObject();
    }


    /**
     * @throws Exception
     */
    private function runHandlers(): void
    {
        if (!$this->isPrivateChat()) {
            return;
        }

        // Run scene handlers
        foreach ($this->scenes as $scene => $sceneClassName) {

            if ($this->isScene($scene)) {
                /** @var TestScene $sceneClassName */
                $sceneClass = new $sceneClassName($this);
                $sceneClass->runSteps();
                return;
            }
        }

        // Run handlers
        switch (true) {
            case $this->isCommand():
                $this->runCommandHandlers();
                break;
            case $this->isCallbackQuery():
                $this->runCbHandlers();
                break;
            case $this->isMessage():
                $this->runMessageHandlers();
                break;
        }
    }


    private function runCommandHandlers(): void
    {
        $command = $this->getCommand();
        $handler = TgHelper::get($this->commandHandlers, $command);
        TgHelper::console('Command: ' . $command);
        if ($handler) {
            $handler();
        } else {
            // Default command handler
            $this->answer('Command not found');
        }
    }


    private function runCbHandlers(): void
    {
        $handler = TgHelper::get($this->cbHandlers, $this->getCallbackQuery());
        TgHelper::console('Callback Query: ' . $this->getCallbackQuery());
        if ($handler) {
            $handler();
        } else {
            $defaultHandler = TgHelper::get($this->cbHandlers, 'any');
            if ($defaultHandler) {
                TgHelper::console('Callback Query any handler');
                $defaultHandler();
            }
        }
    }


    private function runMessageHandlers(): void
    {
        foreach ($this->handlers as $type => $handler) {
            if ($this->isMessageType($type)) {
                TgHelper::console('Handler: ' . $type);
                $handler($this);
                continue;
            }

            if (empty($type)) {
                TgHelper::console('Any message handler');
                $handler($this);
            }
        }
    }


    public function use(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }


    public function registerScene(string $scene, string $sceneClass): void
    {
        $this->scenes[$scene] = $sceneClass;
    }


    /**
     * @throws Exception
     */
    public function startScene(string $sceneKey): void
    {
        $sceneClassName = $this->scenes[$sceneKey];
        TgHelper::console($sceneClassName);
        if (empty($sceneClassName)) {
            throw new Exception('Scene not found');
        }

        /** @var TestScene $sceneClass */
        $sceneClass = new $sceneClassName($this);
        $sceneClass->start();
    }


    public function on(string $type, callable ...$middlewares): void
    {
        $this->handlers[$type] = $this->getProcessedCallback($middlewares);
    }


    public function onCommand(string $command, callable ...$middlewares): void
    {
        $this->commandHandlers[$command] = $this->getProcessedCallback($middlewares);
    }


    public function onCallbackQuery(string $cbQuery, callable ...$middlewares): void
    {
        $this->cbHandlers[$cbQuery] = $this->getProcessedCallback($middlewares);
    }


    public function onAnyCallbackQuery(callable ...$middlewares): void
    {
        $this->cbHandlers['any'] = fn() => $this->callMiddlewares($middlewares, $this);
    }


    public function answerCbQuery(array $options = []): void
    {
        if (!$this->isCallbackQuery()) {
            return;
        }
        $cbQueryId = TgHelper::get($this->input, 'callback_query.id');
        $options = array_merge([
            'callback_query_id' => $cbQueryId
        ], $options);

        $this->sendRequest('answerCallbackQuery', $options);
    }


    public function answerWithPhoto(string $photo, array $options = []): void
    {
        $chatId = TgHelper::get($this->message, 'chat.id');
        $this->sendPhoto($chatId, $photo, $options);
    }


    public function answerHtml(string $text, array $options = []): void
    {
        $options = array_merge([
            'parse_mode' => 'HTML'
        ], $options);

        $this->answer($text, $options);
    }


    public function answer($text, array $options = []): void
    {
        $chatId = TgHelper::get($this->message, 'chat.id');
        $this->sendMessage($chatId, $text, $options);
    }


    public function sendMessage($chatId, $text, array $options = []): void
    {
        $data = array_merge([
            'chat_id' => $chatId,
            'text' => $text
        ], $options);

        $this->sendRequest('sendMessage', $data);
    }


    public function sendPhoto($chatId, $photo, array $options = []): void
    {
        $data = array_merge([
            'chat_id' => $chatId,
            'photo' => $photo
        ], $options);

        $this->sendRequest('sendPhoto', $data);
    }


    private function sendRequest($method, $data): array
    {
        $requestUrl = $this->apiUrl . $method;
        return self::sendRequestStatic($requestUrl, $data);
    }


    public static function sendRequestStatic(string $url, array $data = []): array
    {
        try {
            $client = new Client();
            $response = $client->request('POST', $url, [
                'json' => $data
            ]);

            $responseStr = $response->getBody()->getContents();
            return json_decode($responseStr, true);
        } catch (Exception $e) {
            TgHelper::console("TgBot::sendRequestStatic -> " . $e->getMessage());
            return [];
        }
    }


    private function getProcessedCallback(array $middlewares): Closure
    {
        $middlewares = array_merge($this->middlewares, $middlewares);
        return fn() => $this->callMiddlewares($middlewares, $this);
    }


    private function callMiddlewares(array $middlewares, TgBot $ctx): void
    {
        $middleware = array_shift($middlewares);

        if (count($middlewares) > 0) {
            $middleware($ctx, fn() => $this->callMiddlewares($middlewares, $ctx));
        } else {
            $middleware($ctx);
        }
    }


    /**
     * @throws GuzzleException
     */
    public function getPhotoByFileId(string $fileId): ?string
    {
        $fileInfo = $this->sendRequest('getFile', [
            'file_id' => $fileId
        ]);

        if (TgHelper::get($fileInfo, 'ok')) {
            $filePath = TgHelper::get($fileInfo, 'result.file_path');
            return "{$this->host}file/bot$this->token/$filePath";
        }

        return null;
    }
}

