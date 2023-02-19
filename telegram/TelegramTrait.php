<?php

namespace telegram;

use Exception;

trait TelegramTrait
{

    public array $message = [];

    public function getMessageType(string $type): ?string
    {
        return get($this->message, $type);
    }

    public function isMessageType(string $type): bool
    {
        return get($this->message, $type) !== null;
    }


    /**
     * @throws Exception
     */
    public function isScene(string $scene): bool
    {
        return $this->getRedis()->exists($scene);
    }

    public function isCbEquals(string $cb): bool
    {
        return $this->getCallbackQuery() === $cb;
    }


    public function isCommandEquals(string $command): bool
    {
        return $this->getCommand() === $command;
    }


    public function isCommand(): bool
    {
        $text = get($this->input, 'message.text');
        return self::isCommandStatic($text);
    }

    public static function isCommandStatic($text): bool
    {
        return get($text, 0) === '/';
    }

    public function isPrivateChat(): bool
    {
        $cbChat = get($this->input, 'callback_query.message.chat');
        $chat = get($this->input, 'message.chat');
        $chat = $cbChat ?: $chat;
        return self::isPrivateChatStatic($chat);
    }

    public static function isPrivateChatStatic($chat): bool
    {
        return get($chat, 'type') === 'private';
    }


    public function getPhoto(): ?array
    {
        return get($this->input, 'message.photo');
    }


    public function getCallbackQuery(): ?string
    {
        return get($this->input, 'callback_query.data');
    }


    // get command name
    public function getCommand(): ?string
    {
        $text = get($this->input, 'message.text');
        return self::getCommandStatic($text);
    }

    public static function getCommandStatic($text): ?string
    {
        $text = trim($text);
        $text = explode(' ', $text);
        $text = $text[0];
        return str_replace('/', '', $text);
    }


    public function getFromId(): int
    {
        $cbFrom = get($this->input, 'callback_query.from');
        return $cbFrom ? get($cbFrom, 'id') : get($this->message, 'from.id');
    }


    public function isCallbackQuery(): bool
    {
        return get($this->input, 'callback_query') !== null;
    }


    public function getMessageObject(): array
    {
        if (get($this->input, 'message') !== null) {
            return get($this->input, 'message');
        }

        if (get($this->input, 'callback_query.message') !== null) {
            return get($this->input, 'callback_query.message');
        }

        return [];
    }

    public function isText(): bool
    {
        return get($this->message, 'text') !== null;
    }

    public function getText(): ?string
    {
        return get($this->message, 'text');
    }

    public function getContact(): ?array
    {
        return get($this->message, 'contact');
    }

    public function getFirstName(): ?string
    {
        return get($this->message, 'from.first_name');
    }

    public function getLastName(): ?string
    {
        return get($this->message, 'from.last_name');
    }

    public function getUserName(): ?string
    {
        return get($this->message, 'from.username');
    }

    public function getLanguageCode(): ?string
    {
        return get($this->message, 'from.language_code');
    }

    public function getCommandArgs(): array
    {
        return explode(' ', $this->getCommandArgString());
    }

    public function getCommandArg(int $index): ?string
    {
        return get($this->getCommandArgs(), $index);
    }

    public function getCommandArgCount(): int
    {
        return count($this->getCommandArgs());
    }

    public function getCommandArgString(): ?string
    {
        // TODO: Implement getCommandArgString() method.
        return substr($this->getCommand(), 1);
    }

    public function isMessage(): bool
    {
        return get($this->input, 'message') !== null;
    }


}