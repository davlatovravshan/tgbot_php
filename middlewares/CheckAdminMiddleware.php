<?php

namespace middlewares;

use telegram\Telegram;

class CheckAdminMiddleware
{
    public function __invoke(Telegram $ctx, $next): void
    {
        if ($ctx->getFromId() == ADMIN_ID) {
            $next();
        }
    }
}