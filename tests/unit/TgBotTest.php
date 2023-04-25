<?php

namespace app\tests\unit;

use app\core\scenes\BaseScene;
use PHPUnit\Framework\TestCase;

class TgBotTest extends TestCase
{
    public function testGetSceneKey(): void
    {
        $this->assertEquals('scene_test_123', BaseScene::getSceneKey('test', 123));
        $this->assertEquals('scene_test_123_data', BaseScene::getSceneKey('test', 123, true));
    }
}