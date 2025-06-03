<?php

namespace App\Providers;

use App\Interfaces\AiChatInterface;
use App\Services\DeepseekService;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiChatInterface::class, function ($app) {
            $provider = config('services.ai.provider', 'openai');

            return match ($provider) {
                'deepseek' => $app->make(DeepseekService::class),
                default => $app->make(OpenAIService::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
