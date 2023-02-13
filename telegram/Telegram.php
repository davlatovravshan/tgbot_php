<?php

namespace telegram;

use Predis\Client as PredisClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use telegram\interfaces\TelegramInterface;


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
     * @var PredisClient
     */
    public PredisClient $redis;


    /**
     *
     */
    public const REDIS_KEY = 'telegram';


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
     * @param array $options
     * @return void
     */
    public function initRedis(array $options = []): void
    {
        $this->redis = new PredisClient($options);
        $this->redis->connect();
    }


    /**
     * @return string|null
     */
    public function getRedisData(): ?string
    {
        return $this->redis->get(self::REDIS_KEY);
    }


    /**
     *
     * @param callable ...$middlewares
     */
    public function onMessage(callable ...$middlewares): void
    {
        if ($this->isMessage()) {
            $this->callMiddlewares($middlewares, $this);
        }
    }



    /**
     *
     * @param string $command
     * @param callable ...$middlewares
     */
    public function onCommand(string $command, callable ...$middlewares): void
    {
        if ($this->isCommand() && $this->getCommand() == $command) {
            $this->callMiddlewares($middlewares, $this);
        }
    }



    /**
     *
     * @param string $cbQuery
     * @param callable ...$middlewares
     */
    public function onCallbackQuery(string $cbQuery, callable ...$middlewares): void
    {
        if ($this->isCallbackQuery() && $this->getCallbackQuery() == $cbQuery) {
            $this->callMiddlewares($middlewares, $this);
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
        $chatId = get($this->message, 'chat.id');
        $this->sendMessage($chatId, $text, $options);
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
     * @param $method
     * @param $data
     * @return StreamInterface|null
     * @throws GuzzleException
     */
    public function sendRequest($method, $data): StreamInterface|null
    {
        if ($this->answered) {
            return null;
        }

        $requestUrl = $this->apiUrl . $method;

        $client = new Client();
        $response = $client->request('POST', $requestUrl, [
            'json' => $data
        ]);
        $this->answered = true;

        return $response->getBody();
    }
}