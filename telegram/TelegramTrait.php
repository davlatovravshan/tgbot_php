<?php

namespace telegram;

trait TelegramTrait
{

    public mixed $message;

    public function isCommand(): bool
    {
        $text = get($this->input, 'message.text');
        return self::isCommandStatic($text);
    }

    public static function isCommandStatic($text): bool
    {
        return str_starts_with($text, '/');
    }



    public function getPhoto(): ?array
    {
        return get($this->input, 'message.photo');
    }


    public function getCallbackQuery(): string
    {
        return get($this->input, 'callback_query.data');
    }


    // get command name
    public function getCommand(): string
    {
        $text = get($this->input, 'message.text');
        return self::getCommandStatic($text);
    }

    public static function getCommandStatic($text): string
    {
        $text = trim($text);
        $text = explode(' ', $text);
        $text = $text[0];
        return str_replace('/', '', $text);
    }


    public function getFromId(): int
    {
        return get($this->message, 'from.id');
    }


    public function isCallbackQuery(): bool
    {
        return get($this->input, 'callback_query') !== null;
    }


    public function getMessageObject()
    {
        if (get($this->input, 'message') !== null) {
            return get($this->input, 'message');
        }

        if (get($this->input, 'callback_query.message') !== null) {
            return get($this->input, 'callback_query.message');
        }

        return null;
    }

    public function isText(): bool
    {
        return get($this->message, 'text') !== null;
    }

    public function getText(): string
    {
        return get($this->message, 'text');
    }

    public function getChatId(): int
    {
        return get($this->message, 'chat.id');
    }

    public function getFirstName(): string
    {
        return get($this->message, 'from.first_name');
    }

    public function getLastName(): string
    {
        return get($this->message, 'from.last_name');
    }

    public function getUserName(): string
    {
        return get($this->message, 'from.username');
    }

    public function getLanguageCode(): string
    {
        return get($this->message, 'from.language_code');
    }

    public function getCommandArgs(): array
    {
        return explode(' ', $this->getCommandArgString());
    }

    public function getCommandArg(int $index): string
    {
        return get($this->getCommandArgs(), $index);
    }

    public function getCommandArgCount(): int
    {
        return count($this->getCommandArgs());
    }

    public function getCommandArgString(): string
    {
        // TODO: Implement getCommandArgString() method.
        return substr($this->getCommand(), 1);
    }

    public function isMessage(): bool
    {
        return get($this->input, 'message') !== null;
    }

}