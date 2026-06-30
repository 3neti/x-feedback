<?php

namespace LBHurtado\XFeedback;

use Illuminate\Support\ServiceProvider;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRuntimeContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Contracts\FeedbackEventMapperRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackJournalReceiptMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackReceiptHandoffMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Services\FeedbackChannelRegistry;
use LBHurtado\XFeedback\Services\FeedbackChannelSelector;
use LBHurtado\XFeedback\Services\FeedbackDeliveryAttemptRuntime;
use LBHurtado\XFeedback\Services\FeedbackDeliveryPlanner;
use LBHurtado\XFeedback\Services\FeedbackDispatchPreparer;
use LBHurtado\XFeedback\Services\FeedbackDispatcher;
use LBHurtado\XFeedback\Services\FeedbackEventMapperRegistry;
use LBHurtado\XFeedback\Services\FeedbackJournalReceiptMapper;
use LBHurtado\XFeedback\Services\FeedbackReceiptHandoffMapper;
use LBHurtado\XFeedback\Services\FeedbackTemplateRegistry;
use LBHurtado\XFeedback\Services\FeedbackTemplateResolver;
use LBHurtado\XFeedback\Services\InMemoryFeedbackDeliveryAttemptRecorder;
use LBHurtado\XFeedback\Services\NullFeedbackCredentialResolver;

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

        $this->app->singleton(FeedbackTemplateRegistryContract::class, function (): FeedbackTemplateRegistry {
            return new FeedbackTemplateRegistry((array) config('x-feedback.templates', []));
        });

        $this->app->singleton(FeedbackTemplateResolverContract::class, FeedbackTemplateResolver::class);
        $this->app->singleton(FeedbackChannelSelectorContract::class, FeedbackChannelSelector::class);
        $this->app->singleton(FeedbackDeliveryPlannerContract::class, FeedbackDeliveryPlanner::class);
        $this->app->singleton(FeedbackDeliveryAttemptRuntimeContract::class, FeedbackDeliveryAttemptRuntime::class);
        $this->app->singleton(FeedbackDeliveryAttemptRecorderContract::class, InMemoryFeedbackDeliveryAttemptRecorder::class);
        $this->app->singleton(FeedbackDispatchPreparerContract::class, FeedbackDispatchPreparer::class);
        $this->app->singleton(FeedbackJournalReceiptMapperContract::class, FeedbackJournalReceiptMapper::class);
        $this->app->singleton(FeedbackReceiptHandoffMapperContract::class, FeedbackReceiptHandoffMapper::class);
        $this->app->singleton(FeedbackCredentialResolverContract::class, NullFeedbackCredentialResolver::class);
        $this->app->singleton(FeedbackDispatcherContract::class, FeedbackDispatcher::class);
        $this->app->singleton(FeedbackEventMapperRegistryContract::class, function ($app): FeedbackEventMapperRegistry {
            return new FeedbackEventMapperRegistry(
                container: $app,
                mappers: (array) config('x-feedback.mappers', []),
            );
        });
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
