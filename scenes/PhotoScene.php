<?php

namespace scenes;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use telegram\scenes\BaseScene;
use telegram\Telegram;


class PhotoScene extends BaseScene
{

    public function __construct(Telegram $ctx)
    {
        $this->sceneName = 'photo';
        parent::__construct($ctx);
    }


    /**
     * @throws GuzzleException
     */
    public function onStart(): void
    {
        $this->ctx->answer("Hi {$this->ctx->getFirstName()}!\nUpload your photo");
    }


    /**
     * @throws Exception
     */
    public function initHandlers(): void
    {
        $this->handle(function (Telegram $ctx, PhotoScene $scene) {
            $photo = $ctx->getPhoto();
            if (empty($photo)) {
                $ctx->answer('Upload photo');
                return;
            }
            $photo = end($photo);
            $fileId = get($photo, 'file_id');
            $scene->appendData('photo', $fileId);
            $ctx->answer('Add description');
            $scene->next();
        });

        $this->handle(function (Telegram $ctx, PhotoScene $scene) {
            $sceneData = $scene->getData();
            $ctx->answerWithPhoto(get($sceneData, 'photo'), [
                'caption' => $ctx->getText()
            ]);
        });

        $scene = $this;

        $this->ctx->onCommand('cancel', function (Telegram $ctx) use ($scene) {
            $ctx->answer('Canceled');
            $scene->finish();
        });

        $this->ctx->onCommand('photo', fn() => $scene->restart());
    }

}