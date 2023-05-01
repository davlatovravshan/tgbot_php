<?php

namespace app\scenes;

use app\core\scenes\SceneStep;
use app\core\TgHelper;
use Exception;

trait SceneTrait
{
    private function getNextStepName(): ?string
    {
        $stepIndex = $this->getCurrentStepIndex();
        TgHelper::console('$this->getCurrentStepIndex(): ' . $stepIndex);
        $stepIndex = $stepIndex !== null ? $stepIndex + 1 : 0;
        return TgHelper::get(array_keys($this->steps), $stepIndex);
    }

    private function getCurrentStepIndex(): ?int
    {
        $stepName = $this->getCurrentStepName();
        if (!empty($stepName)) {
            return array_search($stepName, array_keys($this->steps));
        }
        return null;
    }

    private function getCurrentStepName(): ?string
    {
        try {
            return $this->ctx->getCache()->get($this->getSceneKey());
        } catch (Exception $e) {
            TgHelper::console($e->getMessage());
            return null;
        }
    }

    private function getCurrentStep(): ?SceneStep
    {
        $stepName = $this->getCurrentStepName();
        return $this->steps[$stepName] ?? null;
    }

    private function isCancel(): bool
    {
        return $this->isActionEquals(SceneCbEnum::CANCEL);
    }

    private function isBack(): bool
    {
        return $this->isActionEquals(SceneCbEnum::BACK);
    }

    private function isNext(): bool
    {
        return $this->isActionEquals(SceneCbEnum::NEXT);
    }

    private function isActionEquals(SceneCbEnum $cbEnum): bool
    {
        return $this->ctx->isCbEquals($cbEnum->value) || $this->ctx->isCommandEquals($cbEnum->value);
    }

    protected function getCbBtn(string $text, SceneCbEnum $cbEnum): array
    {
        return [
            'text' => $text,
            'callback_data' => $cbEnum->value,
        ];
    }
}