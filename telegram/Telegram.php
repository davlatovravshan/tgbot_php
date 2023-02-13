<?php

namespace telegram;

use Exception;
use Predis\Client as PredisClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use telegram\interfaces\TelegramInterface;
use telegram\scenes\BaseScene;


/**
 *  Telegram bot class
 */
class Telegram implements TelegramInterface
{

    use TelegramTrait;

    /**
     * @var string
     */
    public string $token;


    /**
     * @var string
     */
    public string $apiUrl;


    /**
     * @var mixed
     */
    public mixed $input;


    /**
     * @var bool
     */
    public bool $answered = false;


    /**
     * @var PredisClient|null
     */
    private ?PredisClient $redis = null;


    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
        $this->apiUrl = API_URL . "bot$this->token/";

        $this->loadInput();
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
     * @param string $sceneClass
     * @return void
     * @throws Exception
     */
    public function initScene(string $sceneClass): void
    {
        if (!class_exists($sceneClass)) {
            throw new Exception("Scene class $sceneClass not found");
        }

        /** @var BaseScene $scene */
        $scene = new $sceneClass($this);

        if (!($scene instanceof BaseScene)) {
            throw new Exception("Scene class $sceneClass must be inherited from BaseScene");
        }

        $scene->initHandlers();
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
            throw new Exception('Cannot connect to Redis: ' . $e->getMessage());
        }
    }


    /**
     * @return PredisClient
     * @throws Exception
     */
    public function getRedis(): PredisClient
    {
        if (!$this->redis) {
            $this->initRedis();
        }
        return $this->redis;
    }


    /**
     *
     * @param callable ...$middlewares
     */
    public function onMessage(callable ...$middlewares): void
    {
        if ($this->answered) {
            return;
        }

        if ($this->isMessage()) {
            $this->callMiddlewares($middlewares, $this);
            $this->answered = true;
        }
    }


    /**
     *
     * @param string $command
     * @param callable ...$middlewares
     */
    public function onCommand(string $command, callable ...$middlewares): void
    {
        if ($this->answered) {
            return;
        }

        if ($this->isCommand() && $this->getCommand() == $command) {
            $this->callMiddlewares($middlewares, $this);
            $this->answered = true;
        }
    }


    /**
     *
     * @param string $cbQuery
     * @param callable ...$middlewares
     */
    public function onCallbackQuery(string $cbQuery, callable ...$middlewares): void
    {
        if ($this->answered) {
            return;
        }

        if ($this->isCallbackQuery() && $this->getCallbackQuery() == $cbQuery) {
            $this->callMiddlewares($middlewares, $this);
            $this->answered = true;
        }
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
        if ($this->answered) {
            return;
        }

        $chatId = get($this->message, 'chat.id');
        $this->sendPhoto($chatId, $photo, $options);
        $this->answered = true;
    }


    /**
     * @param $text
     * @return void
     * @throws GuzzleException
     */
    public function answerCbQueryWithText($text): void
    {
        $this->answerCbQuery();
        $this->answer($text);
    }


    /**
     * @param $text
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answer($text, array $options = []): void
    {
        if ($this->answered) {
            return;
        }

        $chatId = get($this->message, 'chat.id');
        $this->sendMessage($chatId, $text, $options);
        $this->answered = true;
    }


    /**
     * @param array $middlewares
     * @param Telegram $ctx
     * @return void
     */
    private function callMiddlewares(array $middlewares, Telegram $ctx): void
    {
        $middleware = array_shift($middlewares);

        if (count($middlewares) > 0) {
            $middleware($ctx, fn() => $this->callMiddlewares($middlewares, $ctx));
        } else {
            $middleware($ctx);
        }
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
    public function sendRequest($method, $data): StreamInterface|null
    {
        $requestUrl = $this->apiUrl . $method;

        $client = new Client();
        $response = $client->request('POST', $requestUrl, [
            'json' => $data
        ]);

        return $response->getBody();
    }
}