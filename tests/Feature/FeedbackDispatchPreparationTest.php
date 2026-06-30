<?php

use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Contracts\FeedbackReceiptHandoffMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackContextData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDispatchPreparationData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackTemplateData;
use LBHurtado\XFeedback\Services\FeedbackDispatchPreparer;
use LBHurtado\XFeedback\Services\FeedbackReceiptHandoffMapper;

it('prepares a dispatch by resolving templates and then planning selected delivery channels', function () {
    app(FeedbackTemplateRegistryContract::class)->register(new FeedbackTemplateData(
        key: 'claim.succeeded',
        title: 'Approved for {{ name }}',
        body: 'Claim {{ claim_id }} is ready.',
        locale: 'en',
        profile: 'beneficiary',
        channel: 'sms',
    ));

    $intent = feedbackPreparationIntent(channels: [
        new FeedbackChannelData(key: 'sms', priority: 20),
        new FeedbackChannelData(key: 'email', priority: 10),
    ]);

    $preparation = app(FeedbackDispatchPreparerContract::class)->prepare($intent, new FeedbackChannelSelectionPolicyData(
        preferred_channels: ['sms'],
    ));

    expect($preparation)->toBeInstanceOf(FeedbackDispatchPreparationData::class)
        ->and($preparation->intent)->not->toBe($intent)
        ->and($preparation->intent->message->title)->toBe('Approved for Ana')
        ->and($preparation->intent->message->body)->toBe('Claim claim-1 is ready.')
        ->and($preparation->plan->items)->toHaveCount(2)
        ->and($preparation->plan->items[0]->channel)->toBe('sms')
        ->and($preparation->plan->items[1]->channel)->toBe('email')
        ->and($preparation->correlation_id)->toBe('execution-1')
        ->and($preparation->causation_id)->toBe('journal-1')
        ->and($intent->message->title)->toBe('');
});

it('does not dispatch provider delivery while preparing a dispatch', function () {
    $preparation = app(FeedbackDispatchPreparerContract::class)->prepare(new \LBHurtado\XFeedback\Data\FeedbackIntentData(
        key: 'operator.alert',
        message: new FeedbackMessageData(title: 'Manual review', body: 'Review the claim.'),
        recipients: [
            new FeedbackRecipientData(type: 'operator', id: 'operator-1', email: 'operator@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'unregistered-future-channel'),
        ],
    ));

    expect($preparation->plan->items)->toHaveCount(1)
        ->and($preparation->plan->items[0]->channel)->toBe('unregistered-future-channel');
});

it('models provider receipt handoff data without persistence', function () {
    $receipt = new FeedbackProviderReceiptData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        status: FeedbackDeliveryData::StatusDelivered,
        provider_message_id: 'provider-message-1',
        provider_status: 'DELIVERED',
        provider_payload: ['raw' => 'payload'],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        occurred_at: '2026-06-30T12:00:00+08:00',
        meta: ['handoff' => true],
    );

    expect($receipt->intent_key)->toBe('claim.succeeded.claimant')
        ->and($receipt->channel)->toBe('sms')
        ->and($receipt->status)->toBe(FeedbackDeliveryData::StatusDelivered)
        ->and($receipt->provider_message_id)->toBe('provider-message-1')
        ->and($receipt->provider_status)->toBe('DELIVERED')
        ->and($receipt->provider_payload)->toBe(['raw' => 'payload'])
        ->and($receipt->correlation_id)->toBe('execution-1')
        ->and($receipt->causation_id)->toBe('journal-1')
        ->and($receipt->occurred_at)->toBe('2026-06-30T12:00:00+08:00')
        ->and($receipt->meta)->toBe(['handoff' => true]);
});

it('maps delivery results into provider receipt handoff payloads without storing them', function () {
    $delivery = new FeedbackDeliveryData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        status: FeedbackDeliveryData::StatusSent,
        provider_message_id: 'provider-message-1',
        result: ['provider_status' => 'SENT', 'accepted_at' => '2026-06-30T12:00:00+08:00'],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
    );

    $receipt = app(FeedbackReceiptHandoffMapperContract::class)->fromDelivery(
        delivery: $delivery,
        providerPayload: ['raw' => 'payload'],
        occurredAt: '2026-06-30T12:01:00+08:00',
    );

    expect($receipt)->toBeInstanceOf(FeedbackProviderReceiptData::class)
        ->and($receipt->intent_key)->toBe('claim.succeeded.claimant')
        ->and($receipt->channel)->toBe('sms')
        ->and($receipt->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($receipt->provider_message_id)->toBe('provider-message-1')
        ->and($receipt->provider_status)->toBe('SENT')
        ->and($receipt->provider_payload)->toBe(['raw' => 'payload'])
        ->and($receipt->correlation_id)->toBe('execution-1')
        ->and($receipt->causation_id)->toBe('journal-1')
        ->and($receipt->occurred_at)->toBe('2026-06-30T12:01:00+08:00');
});

it('binds dispatch preparation and receipt handoff seams for package consumers', function () {
    expect(app(FeedbackDispatchPreparerContract::class))->toBeInstanceOf(FeedbackDispatchPreparer::class)
        ->and(app(FeedbackDispatchPreparerContract::class))->toBe(app(FeedbackDispatchPreparerContract::class))
        ->and(app(FeedbackReceiptHandoffMapperContract::class))->toBeInstanceOf(FeedbackReceiptHandoffMapper::class)
        ->and(app(FeedbackReceiptHandoffMapperContract::class))->toBe(app(FeedbackReceiptHandoffMapperContract::class));
});

it('keeps dispatch preparation independent from provider delivery persistence routes and host packages', function () {
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

function feedbackPreparationIntent(array $recipients = [], array $channels = []): \LBHurtado\XFeedback\Data\FeedbackIntentData
{
    return new \LBHurtado\XFeedback\Data\FeedbackIntentData(
        key: 'claim.succeeded.claimant',
        message: new FeedbackMessageData(
            title: '',
            body: '',
            locale: 'en',
            template: 'claim.succeeded',
            variables: ['name' => 'Ana', 'claim_id' => 'claim-1'],
        ),
        recipients: $recipients ?: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test', phone: '+639171234567'),
        ],
        channels: $channels ?: [
            new FeedbackChannelData(key: 'null'),
        ],
        context: new FeedbackContextData(
            event_type: 'claim.succeeded',
            source: 'x-change',
            correlation_id: 'execution-1',
            causation_id: 'journal-1',
            subject_id: 'claim-1',
            subject_type: 'claim',
            meta: ['feature_profile' => 'beneficiary'],
        ),
    );
}
