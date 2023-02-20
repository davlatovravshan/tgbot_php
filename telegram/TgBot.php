<?php

namespace telegram;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Predis\Client as PredisClient;
use Psr\Http\Message\StreamInterface;
use telegram\interfaces\TelegramInterface;
use telegram\scenes\BaseScene;


/**
 *  Telegram bot class
 */
class TgBot implements TelegramInterface
{

    use TelegramTrait;

    /**
     * @var string
     */
    private string $token;


    /**
     * @var string
     */
    private string $apiUrl;


    /**
     * @var mixed
     */
    private $input;


    /**
     * @var PredisClient|null
     */
    private ?PredisClient $redis = null;


    /**
     * @var array
     */
    private array $options;


    /**
     * @var array
     */
    private array $steps = [];


    /**
     * @var array
     */
    private array $handlers = [];


    /**
     * @var array
     */
    private array $commandHandlers = [];


    /**
     * @var array
     */
    private array $cbHandlers = [];


    /**
     * @var array
     */
    private array $scenes = [];


    /**
     * @var array
     */
    private array $sceneHandlers = [];


    /**
     *
     */
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


    /**
     * @param string $token
     * @param array $options
     * @throws Exception
     * @throws GuzzleException
     */
    public function __construct(string $token, array $options = [])
    {
        $defaultOptions = [
            'webhook' => false,
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ];

        $API_URL = 'https://api.telegram.org/';

        $this->token = $token;
        $this->apiUrl = $API_URL . "bot$this->token/";
        $this->options = array_merge($defaultOptions, $options);

        $this->initRedis();

        /*$webhookUrl = get($this->options, 'webhook');
        if (!empty($webhookUrl)) {
            $this->setWebhook($webhookUrl, get($this->options, 'webhook.options') ?? []);
        }*/
    }


    /**
     * @param string $url
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function setWebhook(string $url, array $options = []): void
    {
        $options['url'] = $url;
        $this->sendRequest('setWebhook', $options);
    }


    /**
     * @param array $options
     * @return void
     * @throws Exception
     */
    private function initRedis(array $options = []): void
    {
        try {
            $this->redis = new PredisClient($options);
            $this->redis->connect();
        } catch (Exception $e) {
            console('Cannot connect to Redis: ' . $e->getMessage());
        }
    }


    /**
     * @return PredisClient
     * @throws Exception
     */
    public function getRedis(): PredisClient
    {
        return $this->redis;
    }


    /**
     * @return void
     * @throws Exception
     * @throws GuzzleException
     */
    public function launch(): void
    {
        if (!get($this->options, 'webhook')) {
            $this->resetAllScenes();
            $this->longPolling();
        } else {
            $this->loadInput();
            $this->runHandlers();
        }
    }


    /**
     * @return void
     */
    public function resetAllScenes(): void
    {
        $sceneKey = BaseScene::getSceneKey('*', "*");
        $sceneKeys = $this->redis->keys($sceneKey);
        foreach ($sceneKeys as $sceneKey) {
            $this->redis->del($sceneKey);
        }
    }


    /**
     * @return void
     */
    public function longPolling(): void
    {
        console('Long polling started');

        $offset = 0;
        while (true) {
            try {
                $update = $this->getUpdates($offset);
                if (empty($update)) {
                    continue;
                }

                $updateId = (int)get($update, 'update_id');
                $offset = $updateId + 1;

                console('Update received');
                console($update);

                $this->input = $update;
                $this->message = $this->getMessageObject();

                // Run all handlers
                $this->runHandlers();
            } catch (GuzzleException $e) {
                console($e->getMessage());
            } catch (Exception $e) {
                console($e->getMessage());
            }
        }
    }


    /**
     * @param int $offset
     * @return array
     * @throws GuzzleException
     */
    public function getUpdates(int $offset = 0): array
    {
        $params = [
            'offset' => $offset,
            'timeout' => 60,
            'allowed_updates' => self::ALLOWED_UPDATES
        ];
        $response = $this->sendRequest('getUpdates', $params);
        $parsedResponse = json_decode($response, true);
        return get($parsedResponse, 'result.0') ?? [];
    }


    /**
     * @return void
     */
    private function loadInput(): void
    {
        $this->input = json_decode(file_get_contents('php://input'), true);
        $this->message = $this->getMessageObject();
    }


    /**
     * @return void
     * @throws Exception
     * @throws GuzzleException
     */
    public function runHandlers(): void
    {
        // Run scene handlers
        foreach ($this->sceneHandlers as $scene => $handler) {
            $sceneName = BaseScene::getSceneKey($scene, $this->getFromId());

            if ($this->isScene($sceneName) && $this->isPrivateChat()) {
                console('Scene: ' . $scene);
                $handler();
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


    /**
     * @return void
     * @throws GuzzleException
     */
    public function runCommandHandlers(): void
    {
        $command = $this->getCommand();
        $handler = get($this->commandHandlers, $command);
        console('Command: ' . $command);
        if ($handler) {
            $handler();
        } else {
            // Default command handler
            $this->answer('Command not found');
        }
    }


    /**
     * @return void
     */
    public function runCbHandlers(): void
    {
        $handler = get($this->cbHandlers, $this->getCallbackQuery());
        console('Callback Query: ' . $this->getCallbackQuery());
        if ($handler) {
            $handler();
        } else {
            $defaultHandler = get($this->cbHandlers, 'any');
            if ($defaultHandler) {
                console('Callback Query any handler');
                $defaultHandler();
            }
        }
    }


    /**
     * @return void
     */
    public function runMessageHandlers(): void
    {
        foreach ($this->handlers as $type => $handler) {
            if ($this->isMessageType($type)) {
                console('Handler: ' . $type);
                $handler($this);
                continue;
            }

            if (empty($type)) {
                console('Any message handler');
                $handler($this);
            }
        }
    }


    /**
     * @param string $scene
     * @param string $sceneClass
     * @return void
     */
    public function registerScene(string $scene, string $sceneClass): void
    {
        $this->scenes[$scene] = $sceneClass;
        $this->sceneHandlers[$scene] = fn() => new $sceneClass($scene, $this);
    }


    /**
     * @throws Exception
     */
    public function startScene(string $scene): void
    {
        $sceneClassName = $this->scenes[$scene];
        console($sceneClassName);
        if (empty($sceneClassName)) {
            throw new Exception('Scene not found');
        }

        /** @var BaseScene $sceneClass */
        $sceneClass = new $sceneClassName($scene, $this);
        $sceneClass->start();
    }


    /**
     * @param string $type
     * @param callable ...$middlewares
     * @return void
     */
    public function on(string $type, callable ...$middlewares): void
    {
        $this->handlers[$type] = fn() => $this->callMiddlewares($middlewares, $this);
    }


    /**
     *
     * @param string $command
     * @param callable ...$middlewares
     */
    public function onCommand(string $command, callable ...$middlewares): void
    {
        $this->commandHandlers[$command] = fn() => $this->callMiddlewares($middlewares, $this);
    }


    /**
     *
     * @param string $cbQuery
     * @param callable ...$middlewares
     */
    public function onCallbackQuery(string $cbQuery, callable ...$middlewares): void
    {
        $this->cbHandlers[$cbQuery] = fn() => $this->callMiddlewares($middlewares, $this);
    }


    /**
     * @param callable ...$middlewares
     * @return void
     */
    public function onAnyCallbackQuery(callable ...$middlewares): void
    {
        $this->cbHandlers['any'] = fn() => $this->callMiddlewares($middlewares, $this);
    }


    /**
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answerCbQuery(array $options = []): void
    {
        $cbQueryId = get($this->input, 'callback_query.id');
        $options = array_merge([
            'callback_query_id' => $cbQueryId
        ], $options);

        $this->sendRequest('answerCallbackQuery', $options);
    }


    /**
     * @param string $photo
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answerWithPhoto(string $photo, array $options = []): void
    {
        $chatId = get($this->message, 'chat.id');
        $this->sendPhoto($chatId, $photo, $options);
    }


    /**
     * @param string $text
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answerHtml(string $text, array $options = []): void
    {
        $options = array_merge([
            'parse_mode' => 'HTML'
        ], $options);

        $this->answer($text, $options);
    }


    /**
     * @param $text
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answer($text, array $options = []): void
    {
        $chatId = get($this->message, 'chat.id');
        $this->sendMessage($chatId, $text, $options);
    }


    /**
     * @param $chatId
     * @param $text
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function sendMessage($chatId, $text, array $options = []): void
    {
        $data = array_merge([
            'chat_id' => $chatId,
            'text' => $text
        ], $options);

        $this->sendRequest('sendMessage', $data);
    }


    /**
     * @param $chatId
     * @param $photo
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function sendPhoto($chatId, $photo, array $options = []): void
    {
        $data = array_merge([
            'chat_id' => $chatId,
            'photo' => $photo
        ], $options);

        $this->sendRequest('sendPhoto', $data);
    }


    /**
     * @param $method
     * @param $data
     * @return StreamInterface|null
     * @throws GuzzleException
     */
    public function sendRequest($method, $data): ?StreamInterface
    {
        $requestUrl = $this->apiUrl . $method;

        $client = new Client();
        $response = $client->request('POST', $requestUrl, [
            'json' => $data
        ]);

        return $response->getBody();
    }


    /**
     * @param array $middlewares
     * @param TgBot $ctx
     * @return void
     */
    private function callMiddlewares(array $middlewares, TgBot $ctx): void
    {
        $middleware = array_shift($middlewares);

        if (count($middlewares) > 0) {
            $middleware($ctx, fn() => $this->callMiddlewares($middlewares, $ctx));
        } else {
            $middleware($ctx);
        }
    }
}


/**
 * @param $var
 * @return void
 */
function console($var)
{
    if (is_object($var)) {
        $var = get_class($var);
    }

    if (is_array($var)) {
        $var = json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    echo $var . PHP_EOL;
}


/**
 * @param $array
 * @param $key
 * @return mixed|null
 */
function get($array, $key)
{
    $keys = explode('.', $key);
    $value = $array;
    foreach ($keys as $key) {
        if (isset($value[$key])) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    return $value;
}