<?php

namespace telegram\interfaces;

interface TelegramInterface
{
    public function onMessage(callable $handler): void;

    public function isText(): bool;
    public function isMessage(): bool;

    public function getText(): string;
    public function getFromId(): int;
    public function getChatId(): int;
    public function getFirstName(): string;
    public function getLastName(): string;
    public function getUserName(): string;
    public function getLanguageCode(): string;
    public function getCommand(): string;
    public function isCommand(): bool;
    public function isCallbackQuery(): bool;
    public function getCallbackQuery(): string;
    public function getCommandArgs(): array;
    public function getCommandArg(int $index): string;
    public function getCommandArgCount(): int;
    public function getCommandArgString(): string;

}