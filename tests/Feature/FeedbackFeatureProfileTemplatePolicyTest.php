<?php

use LBHurtado\XFeedback\Contracts\FeedbackTemplatePolicyResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackContextData;
use LBHurtado\XFeedback\Data\FeedbackFeatureProfileData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackTemplateData;
use LBHurtado\XFeedback\Data\FeedbackTemplateResolutionPolicyData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackTemplateException;
use LBHurtado\XFeedback\Services\FeedbackTemplatePolicyResolver;

it('models feature profiles as institutional experiences rather than languages', function () {
    $profile = new FeedbackFeatureProfileData(
        key: 'dbp',
        name: 'Development Bank of the Philippines',
        template_profile: 'dbp',
        branding: ['logo' => 'dbp.svg', 'tone' => 'formal'],
        variables: ['institution' => 'DBP'],
        actions: [['key' => 'support.contact', 'label' => 'Contact DBP']],
        meta: ['owner' => 'institution'],
    );

    expect($profile->key)->toBe('dbp')
        ->and($profile->template_profile)->toBe('dbp')
        ->and($profile->branding['tone'])->toBe('formal')
        ->and($profile->variables)->toBe(['institution' => 'DBP'])
        ->and($profile->actions)->toBe([['key' => 'support.contact', 'label' => 'Contact DBP']])
        ->and(property_exists($profile, 'locale'))->toBeFalse();
});

it('models template resolution policy with profile and channel fallbacks', function () {
    $policy = new FeedbackTemplateResolutionPolicyData(
        default_profile: 'default',
        profile_fallbacks: ['dbp_pilot' => ['dbp', 'default']],
        channel_fallbacks: ['sms' => ['mail', 'default']],
        feature_profiles: [new FeedbackFeatureProfileData(key: 'dbp', variables: ['institution' => 'DBP'])],
    );

    expect($policy->default_profile)->toBe('default')
        ->and($policy->profile_fallbacks)->toBe(['dbp_pilot' => ['dbp', 'default']])
        ->and($policy->channel_fallbacks)->toBe(['sms' => ['mail', 'default']])
        ->and($policy->feature_profiles[0])->toBeInstanceOf(FeedbackFeatureProfileData::class);
});

it('resolves feature profile and candidate template profiles for package consumers', function () {
    config()->set('x-feedback.template_policy', [
        'default_profile' => 'default',
        'profile_fallbacks' => ['dbp_pilot' => ['dbp', 'default']],
        'feature_profiles' => [
            ['key' => 'dbp_pilot', 'template_profile' => 'dbp', 'variables' => ['institution' => 'DBP']],
        ],
    ]);

    app()->forgetInstance(FeedbackTemplatePolicyResolverContract::class);

    $resolver = app(FeedbackTemplatePolicyResolverContract::class);
    $intent = feedbackTemplatePolicyIntent(profile: 'dbp_pilot');

    expect($resolver)->toBeInstanceOf(FeedbackTemplatePolicyResolver::class)
        ->and($resolver->featureProfileFor($intent)?->key)->toBe('dbp_pilot')
        ->and($resolver->profileCandidatesFor($intent))->toBe(['dbp', 'default'])
        ->and($resolver->variablesFor($intent))->toBe(['institution' => 'DBP']);
});

it('resolves templates through feature profile variables and profile fallback without owning lifecycle meaning', function () {
    config()->set('x-feedback.template_policy', [
        'default_profile' => 'default',
        'profile_fallbacks' => ['dbp_pilot' => ['dbp', 'default']],
        'feature_profiles' => [
            ['key' => 'dbp_pilot', 'template_profile' => 'dbp', 'variables' => ['institution' => 'DBP']],
        ],
    ]);

    app()->forgetInstance(FeedbackTemplatePolicyResolverContract::class);
    app()->forgetInstance(FeedbackTemplateResolverContract::class);

    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: '{{ institution }} claim approved',
        body: 'Claim {{ claim_id }} is ready.',
        profile: 'dbp',
        channel: 'sms',
    ));

    $resolved = app(FeedbackTemplateResolverContract::class)->resolve(
        feedbackTemplatePolicyIntent(profile: 'dbp_pilot', channel: 'sms', variables: ['claim_id' => 'claim-1']),
    );

    expect($resolved->message->title)->toBe('DBP claim approved')
        ->and($resolved->message->body)->toBe('Claim claim-1 is ready.')
        ->and($resolved->message->meta['feature_profile'])->toBe('dbp_pilot')
        ->and($resolved->message->meta['template_profile'])->toBe('dbp')
        ->and($resolved->meta)->toBe(['feature_profile' => 'dbp_pilot']);
});

it('resolves templates through channel fallback policy without leaking unrelated channels', function () {
    config()->set('x-feedback.template_policy', [
        'channel_fallbacks' => ['sms' => ['mail', 'default']],
    ]);

    app()->forgetInstance(FeedbackTemplatePolicyResolverContract::class);
    app()->forgetInstance(FeedbackTemplateResolverContract::class);

    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Mail approved',
        body: 'Mail fallback body',
        profile: 'default',
        channel: 'mail',
    ));

    $resolved = app(FeedbackTemplateResolverContract::class)->resolve(
        feedbackTemplatePolicyIntent(channel: 'sms'),
    );

    expect($resolved->message->title)->toBe('Mail approved')
        ->and($resolved->message->meta['template_channel'])->toBe('mail');
});

it('does not select templates from a mismatched feature profile', function () {
    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'DBP only',
        body: 'DBP only body',
        profile: 'dbp',
        channel: 'sms',
    ));

    app(FeedbackTemplateRegistryContract::class)->template('claim.succeeded', profile: 'dswd', channel: 'sms');
})->throws(UnknownFeedbackTemplateException::class);

it('does not select templates from a mismatched channel without policy fallback', function () {
    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Mail only',
        body: 'Mail only body',
        profile: 'default',
        channel: 'mail',
    ));

    app(FeedbackTemplateRegistryContract::class)->template('claim.succeeded', profile: 'default', channel: 'sms');
})->throws(UnknownFeedbackTemplateException::class);

it('keeps feature profile and template policy independent from persistence authoring UI lifecycle truth and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackTemplatePolicyIntent(?string $profile = 'default', string $channel = 'sms', array $variables = []): FeedbackIntentData
{
    return new FeedbackIntentData(
        key: 'claim.succeeded.claimant',
        message: new FeedbackMessageData(
            title: '',
            body: '',
            template: 'claim.succeeded',
            variables: $variables,
        ),
        channels: [new FeedbackChannelData(key: $channel)],
        context: new FeedbackContextData(
            event_type: 'claim.succeeded',
            meta: ['feature_profile' => $profile],
        ),
        meta: ['feature_profile' => $profile],
    );
}
