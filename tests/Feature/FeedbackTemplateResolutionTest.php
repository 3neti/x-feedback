<?php

use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackContextData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackTemplateData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackTemplateException;
use LBHurtado\XFeedback\Services\FeedbackTemplateRegistry;
use LBHurtado\XFeedback\Services\FeedbackTemplateResolver;

it('models feedback templates with locale profile channel and placeholders', function () {
    $template = new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Claim approved',
        body: 'Hi {{ name }}, claim {{ claim_id }} was approved.',
        locale: 'en',
        profile: 'default',
        channel: 'null',
        variables: ['name' => 'there'],
        actions: [['key' => 'claim.view']],
        artifacts: [['type' => 'receipt']],
    );

    expect($template->key)->toBe('claim.succeeded')
        ->and($template->locale)->toBe('en')
        ->and($template->profile)->toBe('default')
        ->and($template->channel)->toBe('null')
        ->and($template->variables)->toBe(['name' => 'there'])
        ->and($template->actions)->toBe([['key' => 'claim.view']])
        ->and($template->artifacts)->toBe([['type' => 'receipt']]);
});

it('resolves templates by key locale profile and channel with safe fallback', function () {
    $registry = app(FeedbackTemplateRegistryContract::class);
    $registry->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Approved',
        body: 'Default body',
    ));
    $registry->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Approved PH',
        body: 'Kumusta {{ name }}',
        locale: 'fil',
        profile: 'beneficiary',
        channel: 'sms',
    ));

    $exact = $registry->template('claim.succeeded', locale: 'fil', profile: 'beneficiary', channel: 'sms');
    $fallback = $registry->template('claim.succeeded', locale: 'ceb', profile: 'beneficiary', channel: 'sms');

    expect($exact->body)->toBe('Kumusta {{ name }}')
        ->and($fallback->body)->toBe('Default body');
});

it('fails closed for unknown templates before provider delivery', function () {
    app(FeedbackTemplateRegistryContract::class)->template('missing.template');
})->throws(UnknownFeedbackTemplateException::class);

it('resolves intent message templates using locale profile channel and variables', function () {
    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Approved for {{ name }}',
        body: 'Claim {{ claim_id }} is ready.',
        summary: 'Ready',
        locale: 'en',
        profile: 'beneficiary',
        channel: 'null',
        variables: ['name' => 'beneficiary'],
        actions: [['key' => 'claim.view', 'label' => 'View claim']],
    ));

    $intent = new FeedbackIntentData(
        key: 'claim.succeeded.claimant',
        message: new FeedbackMessageData(
            title: '',
            body: '',
            locale: 'en',
            template: 'claim.succeeded',
            variables: ['name' => 'Ana', 'claim_id' => 'claim-1'],
        ),
        recipients: [new FeedbackRecipientData(type: 'claimant', id: 'user-1')],
        channels: [new FeedbackChannelData(key: 'null')],
        context: new FeedbackContextData(
            event_type: 'claim.succeeded',
            meta: ['feature_profile' => 'beneficiary'],
        ),
    );

    $resolved = app(FeedbackTemplateResolverContract::class)->resolve($intent);

    expect($resolved)->not->toBe($intent)
        ->and($resolved->message->title)->toBe('Approved for Ana')
        ->and($resolved->message->body)->toBe('Claim claim-1 is ready.')
        ->and($resolved->message->summary)->toBe('Ready')
        ->and($resolved->message->actions)->toBe([['key' => 'claim.view', 'label' => 'View claim']])
        ->and($intent->message->title)->toBe('');
});

it('leaves intents without a template unchanged', function () {
    $intent = new FeedbackIntentData(
        key: 'operator.alert',
        message: new FeedbackMessageData(title: 'Alert', body: 'Manual review required.'),
    );

    expect(app(FeedbackTemplateResolverContract::class)->resolve($intent))->toBe($intent);
});

it('binds template registry and resolver for package consumers', function () {
    expect(app(FeedbackTemplateRegistryContract::class))->toBeInstanceOf(FeedbackTemplateRegistry::class)
        ->and(app(FeedbackTemplateRegistryContract::class))->toBe(app(FeedbackTemplateRegistryContract::class))
        ->and(app(FeedbackTemplateResolverContract::class))->toBeInstanceOf(FeedbackTemplateResolver::class);
});

it('keeps template resolution independent from provider delivery persistence routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});
