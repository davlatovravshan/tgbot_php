<?php

namespace telegram;

use Exception;
use Predis\Client as PredisClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use telegram\interfaces\TelegramInterface;


/**
 *  Telegram bot class
 */
class TgBot_old implements TelegramInterface
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
     * @var bool
     */
    public bool $answered = false;


    /**
     * @var PredisClient|null
     */
    private ?PredisClient $redis = null;


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
     */
    public function __construct(string $token, array $options = [])
    {
        $API_URL = 'https://api.telegram.org/';

        $this->token = $token;
        $this->apiUrl = $API_URL . "bot$this->token/";

        if (get($options, 'polling')) {
            $this->longPolling();
        } else {
            $this->loadInput();
        }

        $this->initRedis();
    }


    /**
     * @return void
     */
    public function launch(): void
    {
        $this->longPolling();
    }


    /**
     * @return void
     */
    public function longPolling(): void
    {
        console('Long polling started');

        $offset = 0;
        while (true) {
            $update = $this->getUpdates($offset);
            if (empty($update)) {
                continue;
            }

            console('Update received');
            console($update);

            $this->input = $update;
            $this->message = $this->getMessageObject();

            $offset = (int)get($update, 'update_id') + 1;
            sleep(1);
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
     * @param callable ...$middlewares
     * @return void
     */
    public function onAnyCallbackQuery(callable ...$middlewares): void
    {
        if ($this->answered) {
            return;
        }

        if ($this->isCallbackQuery()) {
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
     * @param string $text
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function answerCbQueryWithMsg(string $text, array $options = []): void
    {
        $this->answerCbQuery();
        $this->answer($text, $options);
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
        /*if ($this->answered) {
            return;
        }*/

        $chatId = get($this->message, 'chat.id');
        $this->sendMessage($chatId, $text, $options);
        $this->answered = true;
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
     * @param TgBot_old $ctx
     * @return void
     */
    private function callMiddlewares(array $middlewares, TgBot_old $ctx): void
    {
        $middleware = array_shift($middlewares);

        if (count($middlewares) > 0) {
            $middleware($ctx, fn() => $this->callMiddlewares($middlewares, $ctx));
        } else {
            $middleware($ctx);
        }
    }

    public function on(string $type, callable ...$middlewares): void
    {
        // TODO: Implement on() method.
    }
}