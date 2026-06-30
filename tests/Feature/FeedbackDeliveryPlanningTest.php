<?php

use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanItemData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\FeedbackChannelSelector;
use LBHurtado\XFeedback\Services\FeedbackDeliveryPlanner;

it('models channel selection policies without delivery or persistence concerns', function () {
    $policy = new FeedbackChannelSelectionPolicyData(
        allowed_channels: ['sms', 'email', 'null'],
        preferred_channels: ['email'],
        fallback_channels: ['null'],
        required_channels: ['sms'],
        disabled_channels: ['push'],
        profile: 'beneficiary',
        locale: 'en',
        meta: ['reason' => 'claim-approved'],
    );

    expect($policy->allowed_channels)->toBe(['sms', 'email', 'null'])
        ->and($policy->preferred_channels)->toBe(['email'])
        ->and($policy->fallback_channels)->toBe(['null'])
        ->and($policy->required_channels)->toBe(['sms'])
        ->and($policy->disabled_channels)->toBe(['push'])
        ->and($policy->profile)->toBe('beneficiary')
        ->and($policy->locale)->toBe('en')
        ->and($policy->meta)->toBe(['reason' => 'claim-approved']);
});

it('selects enabled channels by policy using required preferred priority and fallback order', function () {
    $intent = feedbackPlanningIntent(channels: [
        new FeedbackChannelData(key: 'sms', priority: 50),
        new FeedbackChannelData(key: 'email', priority: 20),
        new FeedbackChannelData(key: 'push', priority: 10),
        new FeedbackChannelData(key: 'null', priority: 100),
        new FeedbackChannelData(key: 'disabled', enabled: false, priority: 1),
    ]);

    $selected = app(FeedbackChannelSelectorContract::class)->select($intent, new FeedbackChannelSelectionPolicyData(
        allowed_channels: ['sms', 'email', 'null', 'disabled'],
        preferred_channels: ['email'],
        fallback_channels: ['null'],
        required_channels: ['sms'],
    ));

    expect(array_map(fn (FeedbackChannelData $channel): string => $channel->key, $selected))
        ->toBe(['sms', 'email', 'null']);
});

it('excludes disabled policy channels before delivery planning', function () {
    $intent = feedbackPlanningIntent(channels: [
        new FeedbackChannelData(key: 'sms', priority: 10),
        new FeedbackChannelData(key: 'email', priority: 20),
    ]);

    $selected = app(FeedbackChannelSelectorContract::class)->select($intent, new FeedbackChannelSelectionPolicyData(
        disabled_channels: ['sms'],
    ));

    expect(array_map(fn (FeedbackChannelData $channel): string => $channel->key, $selected))
        ->toBe(['email']);
});

it('plans recipient channel combinations without invoking provider delivery', function () {
    $intent = feedbackPlanningIntent(
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
            new FeedbackRecipientData(type: 'issuer', id: 'issuer-1', email: 'issuer@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'email', priority: 10),
            new FeedbackChannelData(key: 'sms', priority: 20),
        ],
    );

    $plan = app(FeedbackDeliveryPlannerContract::class)->plan($intent, new FeedbackChannelSelectionPolicyData(
        preferred_channels: ['sms'],
    ));

    expect($plan)->toBeInstanceOf(FeedbackDeliveryPlanData::class)
        ->and($plan->intent_key)->toBe('claim.succeeded.claimant')
        ->and($plan->correlation_id)->toBe('execution-1')
        ->and($plan->causation_id)->toBe('journal-1')
        ->and($plan->items)->toHaveCount(4)
        ->and($plan->items[0])->toBeInstanceOf(FeedbackDeliveryPlanItemData::class)
        ->and($plan->items[0]->status)->toBe(FeedbackDeliveryPlanItemData::StatusPlanned)
        ->and($plan->items[0]->channel)->toBe('sms')
        ->and($plan->items[0]->recipient->type)->toBe('claimant')
        ->and($plan->items[1]->channel)->toBe('email')
        ->and($plan->items[2]->recipient->type)->toBe('issuer')
        ->and($plan->items[2]->channel)->toBe('sms');
});

it('does not resolve channel drivers while creating delivery plans', function () {
    $plan = app(FeedbackDeliveryPlannerContract::class)->plan(feedbackPlanningIntent(channels: [
        new FeedbackChannelData(key: 'unregistered-provider-driver'),
    ]));

    expect($plan->items)->toHaveCount(1)
        ->and($plan->items[0]->channel)->toBe('unregistered-provider-driver')
        ->and($plan->items[0]->status)->toBe(FeedbackDeliveryPlanItemData::StatusPlanned);
});

it('binds selector and planner for package consumers', function () {
    expect(app(FeedbackChannelSelectorContract::class))->toBeInstanceOf(FeedbackChannelSelector::class)
        ->and(app(FeedbackChannelSelectorContract::class))->toBe(app(FeedbackChannelSelectorContract::class))
        ->and(app(FeedbackDeliveryPlannerContract::class))->toBeInstanceOf(FeedbackDeliveryPlanner::class)
        ->and(app(FeedbackDeliveryPlannerContract::class))->toBe(app(FeedbackDeliveryPlannerContract::class));
});

it('keeps delivery planning independent from provider delivery persistence routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackPlanningIntent(array $recipients = [], array $channels = []): FeedbackIntentData
{
    return FeedbackIntentData::forEvent(
        key: 'claim.succeeded.claimant',
        eventType: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim approved', body: 'Your claim was approved.'),
        recipients: $recipients ?: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
        ],
        channels: $channels ?: [
            new FeedbackChannelData(key: 'null'),
        ],
        source: 'x-change',
        correlationId: 'execution-1',
        causationId: 'journal-1',
        subjectType: 'claim',
        subjectId: 'claim-1',
    );
}
