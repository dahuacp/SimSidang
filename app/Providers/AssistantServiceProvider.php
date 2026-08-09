<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\VirtualAssistant\LlmProviderInterface;
use App\Services\VirtualAssistant\OpenAiCompatibleProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/assistant.php',
            'assistant'
        );

        $this->app->singleton(LlmProviderInterface::class, function ($app) {
            return new OpenAiCompatibleProvider;
        });
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        RateLimiter::for('assistant', function (Request $request) {
            $perMinute = config('assistant.rate_limit.per_minute', 10);

            return $perMinute > 0
                ? Limit::perMinute($perMinute)->by($request->user()->id ?? $request->ip())
                : Limit::none();
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('assistant_per_conversation', function (Request $request) {
            $perConv = config('assistant.rate_limit.per_conversation', 50);

            $key = Str::slug($request->route('conversation') ?? 'default');

            return $perConv > 0
                ? Limit::perMinute($perConv * 12)->by($key)
                : Limit::none();
        });
    }
}
