<?php

namespace app\scenes;

use app\core\scenes\SceneStep;
use app\core\TgHelper;
use Exception;

trait SceneTrait
{

    protected function getCommonMarkup(array $buttons): array
    {
        $inlineKeyboard = [];

        $firstKeyboardRow = $buttons[0] ?? [];
        if (!empty($firstKeyboardRow)) {
            $inlineKeyboard[] = $this->getActionButtons(...$firstKeyboardRow);
        }

        $secondKeyboardRow = $buttons[1] ?? [];
        if (!empty($secondKeyboardRow)) {
            $inlineKeyboard[] = $this->getActionButtons(...$secondKeyboardRow);
        }

        return [
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
        ];
    }

    protected function getActionButtons(SceneCbEnum ...$buttons): array
    {
        $result = [];
        foreach ($buttons as $button) {
            $result[] = $this->getCbBtn($button->getText(), $button);
        }
        return $result;
    }

    protected function getBackButton(string $text = 'Назад'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::BACK);
    }

    protected function getCancelButton(string $text = 'Отмена'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::CANCEL);
    }

    protected function getNextButton(string $text = 'Далее'): array
    {
        return $this->getCbBtn($text, SceneCbEnum::NEXT);
    }


    protected function getCbBtn(string $text, SceneCbEnum $cbEnum): array
    {
        return [
            'text' => $text,
            'callback_data' => $cbEnum->value,
        ];
    }
}