<?php

namespace scenes;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use telegram\scenes\BaseScene;
use telegram\TgBot;


class PhotoScene extends BaseScene
{

    /**
     * @throws GuzzleException
     */
    public function onStart(): void
    {
        $this->ctx->answer("Hi {$this->ctx->getFirstName()}!\nUpload your photo");
    }


    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function initHandlers(): void
    {
        $this->ctx->getCommand();
        if ($this->ctx->getCommand() === 'cancel') {
            $this->ctx->answer('Canceled');
            $this->finish();
            return;
        }

        $this->handle(function (TgBot $ctx) {
            $photo = $ctx->getPhoto();
            if (empty($photo)) {
                $ctx->answer('Upload photo');
                return;
            }
            $photo = end($photo);
            $fileId = get($photo, 'file_id');
            $this->appendData('photo', $fileId);
            $ctx->answer('Add description');
            $this->next();
        });

        $this->handle(function (TgBot $ctx) {
            $thisData = $this->getData();
            $ctx->answerWithPhoto(get($thisData, 'photo'), [
                'caption' => $ctx->getText()
            ]);
            $this->finish();
        });

    }

}