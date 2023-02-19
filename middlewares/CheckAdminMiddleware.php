<?php

namespace middlewares;

use telegram\TgBot;

class CheckAdminMiddleware
{
    public function __invoke(TgBot $ctx, $next): void
    {
        if ($ctx->getFromId() == ADMIN_ID) {
            $next();
        }
    }
}