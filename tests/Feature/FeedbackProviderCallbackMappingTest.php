<?php

use LBHurtado\XFeedback\Contracts\FeedbackProviderCallbackMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderCallbackData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\FeedbackProviderCallbackMapper;

it('models provider callback facts without owning provider lifecycle truth', function () {
    $callback = new FeedbackProviderCallbackData(
        provider: 'sms-provider',
        channel: 'sms',
        intent_key: 'claim.succeeded.claimant',
        provider_message_id: 'provider-message-1',
        provider_status: 'DELIVERED',
        status: FeedbackDeliveryData::StatusDelivered,
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        payload: ['raw' => 'payload'],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        occurred_at: '2026-06-30T12:00:00+08:00',
        meta: ['signature_valid' => true],
    );

    expect($callback->provider)->toBe('sms-provider')
        ->and($callback->channel)->toBe('sms')
        ->and($callback->intent_key)->toBe('claim.succeeded.claimant')
        ->and($callback->provider_message_id)->toBe('provider-message-1')
        ->and($callback->provider_status)->toBe('DELIVERED')
        ->and($callback->status)->toBe(FeedbackDeliveryData::StatusDelivered)
        ->and($callback->recipient->type)->toBe('claimant')
        ->and($callback->payload)->toBe(['raw' => 'payload'])
        ->and($callback->meta)->toBe(['signature_valid' => true]);
});

it('maps provider callbacks into provider receipt handoff data', function () {
    $receipt = app(FeedbackProviderCallbackMapperContract::class)->toReceipt(providerCallbackFixture());

    expect($receipt)->toBeInstanceOf(FeedbackProviderReceiptData::class)
        ->and($receipt->intent_key)->toBe('claim.succeeded.claimant')
        ->and($receipt->channel)->toBe('sms')
        ->and($receipt->status)->toBe(FeedbackDeliveryData::StatusDelivered)
        ->and($receipt->provider_message_id)->toBe('provider-message-1')
        ->and($receipt->provider_status)->toBe('DELIVERED')
        ->and($receipt->provider_payload)->toBe(['raw' => 'payload'])
        ->and($receipt->correlation_id)->toBe('execution-1')
        ->and($receipt->causation_id)->toBe('journal-1')
        ->and($receipt->occurred_at)->toBe('2026-06-30T12:00:00+08:00')
        ->and($receipt->meta['provider'])->toBe('sms-provider');
});

it('maps provider callbacks into feedback events for downstream mappers', function () {
    $event = app(FeedbackProviderCallbackMapperContract::class)->toEvent(providerCallbackFixture());

    expect($event)->toBeInstanceOf(FeedbackEventData::class)
        ->and($event->type)->toBe('feedback.provider_callback.delivered')
        ->and($event->source)->toBe('sms-provider')
        ->and($event->payload['intent_key'])->toBe('claim.succeeded.claimant')
        ->and($event->payload['provider_message_id'])->toBe('provider-message-1')
        ->and($event->payload['provider_status'])->toBe('DELIVERED')
        ->and($event->payload['status'])->toBe(FeedbackDeliveryData::StatusDelivered)
        ->and($event->correlation_id)->toBe('execution-1')
        ->and($event->causation_id)->toBe('journal-1')
        ->and($event->subject_type)->toBe('feedback_delivery')
        ->and($event->subject_id)->toBe('claim.succeeded.claimant:sms:provider-message-1')
        ->and($event->occurred_at)->toBe('2026-06-30T12:00:00+08:00');
});

it('normalizes provider callback status when explicit delivery status is omitted', function () {
    $callback = new FeedbackProviderCallbackData(
        provider: 'email-provider',
        channel: 'email',
        intent_key: 'claim.failed.operator',
        provider_message_id: 'provider-message-2',
        provider_status: 'BOUNCED',
        recipient: new FeedbackRecipientData(type: 'operator', id: 'operator-1', email: 'operator@example.test'),
    );

    $receipt = app(FeedbackProviderCallbackMapperContract::class)->toReceipt($callback);
    $event = app(FeedbackProviderCallbackMapperContract::class)->toEvent($callback);

    expect($receipt->status)->toBe(FeedbackDeliveryData::StatusFailedFinal)
        ->and($event->type)->toBe('feedback.provider_callback.failed_final');
});

it('binds the provider callback mapper for package consumers', function () {
    expect(app(FeedbackProviderCallbackMapperContract::class))->toBeInstanceOf(FeedbackProviderCallbackMapper::class)
        ->and(app(FeedbackProviderCallbackMapperContract::class))->toBe(app(FeedbackProviderCallbackMapperContract::class));
});

it('keeps provider callback mapping independent from webhook routes provider SDKs persistence and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function providerCallbackFixture(): FeedbackProviderCallbackData
{
    return new FeedbackProviderCallbackData(
        provider: 'sms-provider',
        channel: 'sms',
        intent_key: 'claim.succeeded.claimant',
        provider_message_id: 'provider-message-1',
        provider_status: 'DELIVERED',
        status: FeedbackDeliveryData::StatusDelivered,
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        payload: ['raw' => 'payload'],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        occurred_at: '2026-06-30T12:00:00+08:00',
        meta: ['signature_valid' => true],
    );
}
