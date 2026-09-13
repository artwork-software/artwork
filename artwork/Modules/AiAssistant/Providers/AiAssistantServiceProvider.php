<?php

namespace Artwork\Modules\AiAssistant\Providers;

use Illuminate\Support\ServiceProvider;

class AiAssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 4) . '/config/ai.php',
            'ai'
        );
    }
}
