<?php

namespace LBHurtado\XFeedback;

use Illuminate\Support\ServiceProvider;
use LBHurtado\XFeedback\Contracts\FeedbackActionArtifactRendererContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelContentRendererContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRuntimeContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryConsoleContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Contracts\FeedbackEventMapperRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackInAppNotificationStateManagerContract;
use LBHurtado\XFeedback\Contracts\FeedbackJournalEventMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackJournalReceiptMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackNotificationRouteResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackOperationalMonitorContract;
use LBHurtado\XFeedback\Contracts\FeedbackProviderCallbackMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackReceiptHandoffMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackRetryFreshnessEvaluatorContract;
use LBHurtado\XFeedback\Contracts\FeedbackSuppressionEvaluatorContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplatePolicyResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackUiComponentPresenterContract;
use LBHurtado\XFeedback\Contracts\FeedbackWebhookSenderContract;
use LBHurtado\XFeedback\Data\FeedbackFeatureProfileData;
use LBHurtado\XFeedback\Data\FeedbackTemplateResolutionPolicyData;
use LBHurtado\XFeedback\Services\ConfigFeedbackCredentialResolver;
use LBHurtado\XFeedback\Services\DatabaseFeedbackDeliveryAttemptRecorder;
use LBHurtado\XFeedback\Services\FeedbackActionArtifactRenderer;
use LBHurtado\XFeedback\Services\FeedbackChannelContentRenderer;
use LBHurtado\XFeedback\Services\FeedbackChannelRegistry;
use LBHurtado\XFeedback\Services\FeedbackChannelSelector;
use LBHurtado\XFeedback\Services\FeedbackDeliveryAttemptRuntime;
use LBHurtado\XFeedback\Services\FeedbackDeliveryConsole;
use LBHurtado\XFeedback\Services\FeedbackDeliveryPlanner;
use LBHurtado\XFeedback\Services\FeedbackDispatcher;
use LBHurtado\XFeedback\Services\FeedbackDispatchPreparer;
use LBHurtado\XFeedback\Services\FeedbackEventMapperRegistry;
use LBHurtado\XFeedback\Services\FeedbackInAppNotificationStateManager;
use LBHurtado\XFeedback\Services\FeedbackJournalEventMapper;
use LBHurtado\XFeedback\Services\FeedbackJournalReceiptMapper;
use LBHurtado\XFeedback\Services\FeedbackNotificationRouteResolver;
use LBHurtado\XFeedback\Services\FeedbackOperationalMonitor;
use LBHurtado\XFeedback\Services\FeedbackProviderCallbackMapper;
use LBHurtado\XFeedback\Services\FeedbackReceiptHandoffMapper;
use LBHurtado\XFeedback\Services\FeedbackRetryFreshnessEvaluator;
use LBHurtado\XFeedback\Services\FeedbackSuppressionEvaluator;
use LBHurtado\XFeedback\Services\FeedbackTemplatePolicyResolver;
use LBHurtado\XFeedback\Services\FeedbackTemplateRegistry;
use LBHurtado\XFeedback\Services\FeedbackTemplateResolver;
use LBHurtado\XFeedback\Services\FeedbackUiComponentPresenter;
use LBHurtado\XFeedback\Services\SpatieFeedbackWebhookSender;

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

        $this->app->singleton(FeedbackTemplatePolicyResolverContract::class, function (): FeedbackTemplatePolicyResolver {
            return new FeedbackTemplatePolicyResolver($this->templateResolutionPolicy());
        });

        $this->app->singleton(FeedbackActionArtifactRendererContract::class, function (): FeedbackActionArtifactRenderer {
            return new FeedbackActionArtifactRenderer(
                actionPolicies: (array) config('x-feedback.rendering.actions', []),
                artifactPolicies: (array) config('x-feedback.rendering.artifacts', []),
            );
        });
        $this->app->singleton(FeedbackChannelContentRendererContract::class, FeedbackChannelContentRenderer::class);

        $this->app->singleton(FeedbackNotificationRouteResolverContract::class, function (): FeedbackNotificationRouteResolver {
            return new FeedbackNotificationRouteResolver((array) config('x-feedback.notification_routes', []));
        });

        $this->app->singleton(FeedbackOperationalMonitorContract::class, function ($app): FeedbackOperationalMonitor {
            return new FeedbackOperationalMonitor(
                channels: $app->make(FeedbackChannelRegistryContract::class),
                retryFreshness: $app->make(FeedbackRetryFreshnessEvaluatorContract::class),
                defaultChannels: array_keys((array) config('x-feedback.channels', [])),
            );
        });

        $this->app->singleton(FeedbackTemplateResolverContract::class, FeedbackTemplateResolver::class);
        $this->app->singleton(FeedbackChannelSelectorContract::class, FeedbackChannelSelector::class);
        $this->app->singleton(FeedbackDeliveryPlannerContract::class, FeedbackDeliveryPlanner::class);
        $this->app->singleton(FeedbackDeliveryAttemptRuntimeContract::class, FeedbackDeliveryAttemptRuntime::class);
        $this->app->singleton(FeedbackDeliveryConsoleContract::class, FeedbackDeliveryConsole::class);
        $this->app->singleton(FeedbackDeliveryAttemptRecorderContract::class, DatabaseFeedbackDeliveryAttemptRecorder::class);
        $this->app->singleton(FeedbackInAppNotificationStateManagerContract::class, FeedbackInAppNotificationStateManager::class);
        $this->app->singleton(FeedbackDispatchPreparerContract::class, FeedbackDispatchPreparer::class);
        $this->app->singleton(FeedbackJournalEventMapperContract::class, FeedbackJournalEventMapper::class);
        $this->app->singleton(FeedbackJournalReceiptMapperContract::class, FeedbackJournalReceiptMapper::class);
        $this->app->singleton(FeedbackProviderCallbackMapperContract::class, FeedbackProviderCallbackMapper::class);
        $this->app->singleton(FeedbackReceiptHandoffMapperContract::class, FeedbackReceiptHandoffMapper::class);
        $this->app->singleton(FeedbackRetryFreshnessEvaluatorContract::class, FeedbackRetryFreshnessEvaluator::class);
        $this->app->singleton(FeedbackSuppressionEvaluatorContract::class, FeedbackSuppressionEvaluator::class);
        $this->app->singleton(FeedbackUiComponentPresenterContract::class, FeedbackUiComponentPresenter::class);
        $this->app->singleton(FeedbackCredentialResolverContract::class, ConfigFeedbackCredentialResolver::class);
        $this->app->singleton(FeedbackWebhookSenderContract::class, SpatieFeedbackWebhookSender::class);
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
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'x-feedback');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/x-feedback.php' => config_path('x-feedback.php'),
            ], 'x-feedback-config');
        }

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    private function templateResolutionPolicy(): FeedbackTemplateResolutionPolicyData
    {
        $policy = (array) config('x-feedback.template_policy', []);

        $policy['feature_profiles'] = array_map(
            fn (mixed $profile): mixed => $profile instanceof FeedbackFeatureProfileData || ! is_array($profile)
                ? $profile
                : new FeedbackFeatureProfileData(...$profile),
            (array) ($policy['feature_profiles'] ?? []),
        );

        return new FeedbackTemplateResolutionPolicyData(...$policy);
    }
}
