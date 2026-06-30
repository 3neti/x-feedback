<?php

namespace LBHurtado\XFeedback;

use Illuminate\Support\ServiceProvider;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Services\FeedbackChannelRegistry;
use LBHurtado\XFeedback\Services\FeedbackDispatcher;
use LBHurtado\XFeedback\Services\NullFeedbackCredentialResolver;
use LBHurtado\XFeedback\Services\PassthroughFeedbackTemplateResolver;

final class XFeedbackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/x-feedback.php', 'x-feedback');

        $this->app->singleton(FeedbackChannelRegistryContract::class, function ($app): FeedbackChannelRegistry {
            return new FeedbackChannelRegistry(
                container: $app,
                drivers: (array) config('x-feedback.channels', []),
            );
        });

        $this->app->singleton(FeedbackTemplateResolverContract::class, PassthroughFeedbackTemplateResolver::class);
        $this->app->singleton(FeedbackCredentialResolverContract::class, NullFeedbackCredentialResolver::class);
        $this->app->singleton(FeedbackDispatcherContract::class, FeedbackDispatcher::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/x-feedback.php' => config_path('x-feedback.php'),
            ], 'x-feedback-config');
        }
    }
}
