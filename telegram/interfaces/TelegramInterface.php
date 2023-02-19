<?php

namespace telegram\interfaces;

/**
 *
 */
interface TelegramInterface
{
    /**
     * @param string $type
     * @param callable ...$middlewares
     * @return void
     */
    public function on(string $type, callable ...$middlewares): void;

    /**
     * @return bool
     */
    public function isText(): bool;

    /**
     * @return bool
     */
    public function isMessage(): bool;

    /**
     * @return string
     */
    public function getText(): ?string;

    /**
     * @return int
     */
    public function getFromId(): int;

    /**
     * @return string
     */
    public function getFirstName(): ?string;

    /**
     * @return string
     */
    public function getLastName(): ?string;

    /**
     * @return string
     */
    public function getUserName(): ?string;

    /**
     * @return string
     */
    public function getLanguageCode(): ?string;

    /**
     * @return string
     */
    public function getCommand(): ?string;

    /**
     * @return bool
     */
    public function isCommand(): bool;

    /**
     * @return bool
     */
    public function isCallbackQuery(): bool;

    /**
     * @return string
     */
    public function getCallbackQuery(): ?string;

    /**
     * @return array
     */
    public function getCommandArgs(): array;

    /**
     * @param int $index
     * @return string
     */
    public function getCommandArg(int $index): ?string;

    /**
     * @return int
     */
    public function getCommandArgCount(): int;

    /**
     * @return string
     */
    public function getCommandArgString(): ?string;

}