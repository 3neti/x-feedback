<?php

use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Services\NullFeedbackCredentialResolver;
use LBHurtado\XFeedback\Services\PassthroughFeedbackTemplateResolver;

it('binds the core feedback contracts', function () {
    expect(app(FeedbackDispatcherContract::class))->toBeInstanceOf(FeedbackDispatcherContract::class)
        ->and(app(FeedbackChannelRegistryContract::class))->toBeInstanceOf(FeedbackChannelRegistryContract::class)
        ->and(app(FeedbackTemplateResolverContract::class))->toBeInstanceOf(PassthroughFeedbackTemplateResolver::class)
        ->and(app(FeedbackCredentialResolverContract::class))->toBeInstanceOf(NullFeedbackCredentialResolver::class);
});

it('merges package configuration defaults', function () {
    expect(config('x-feedback.channels.null'))->toBeString();
});

it('publishes the package configuration', function () {
    $provider = app()->getProvider(LBHurtado\XFeedback\XFeedbackServiceProvider::class);

    expect($provider)->not->toBeNull();
});
