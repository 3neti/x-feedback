<?php

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryConsoleContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackProviderResponseData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackDeliveryRecordException;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;
use LBHurtado\XFeedback\Services\FeedbackDeliveryConsole;

it('exposes delivery status read models for console consumers', function () {
    $deliveryId = feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusDelivered,
        channel: 'sms',
        providerMessageId: 'sms-delivered-1',
        providerStatus: 'DELIVERED',
    );

    $status = app(FeedbackDeliveryConsoleContract::class)->status($deliveryId);

    expect($status)->toBeInstanceOf(FeedbackDeliveryConsoleRecordData::class)
        ->and($status->delivery_id)->toBe($deliveryId)
        ->and($status->intent_key)->toBe('claim.succeeded.claimant')
        ->and($status->channel)->toBe('sms')
        ->and($status->status)->toBe(FeedbackDeliveryData::StatusDelivered)
        ->and($status->provider_message_id)->toBe('sms-delivered-1')
        ->and($status->provider_status)->toBe('DELIVERED')
        ->and($status->meta['console_read_only'])->toBeTrue()
        ->and($status->meta)->not->toHaveKey('workflow_status');
});

it('fails closed for missing delivery console records', function () {
    app(FeedbackDeliveryConsoleContract::class)->status('missing-delivery');
})->throws(UnknownFeedbackDeliveryRecordException::class);

it('lists delivery attempt history by correlation id and intent without creating cockpit pages', function () {
    feedbackConsoleRecord(status: FeedbackDeliveryData::StatusSent, channel: 'email', providerMessageId: 'email-1', correlationId: 'execution-1');
    feedbackConsoleRecord(status: FeedbackDeliveryData::StatusFailedRetryable, channel: 'sms', providerMessageId: 'sms-1', correlationId: 'execution-1');
    feedbackConsoleRecord(status: FeedbackDeliveryData::StatusDelivered, channel: 'webhook', providerMessageId: 'webhook-1', correlationId: 'execution-2');

    $history = app(FeedbackDeliveryConsoleContract::class)->history([
        'correlation_id' => 'execution-1',
        'intent_key' => 'claim.succeeded.claimant',
    ]);

    expect($history)->toBeInstanceOf(FeedbackDeliveryConsoleHistoryData::class)
        ->and($history->total)->toBe(2)
        ->and($history->records)->toHaveCount(2)
        ->and($history->records[0]->provider_message_id)->toBe('sms-1')
        ->and($history->records[1]->provider_message_id)->toBe('email-1')
        ->and($history->filters)->toBe([
            'correlation_id' => 'execution-1',
            'intent_key' => 'claim.succeeded.claimant',
        ])
        ->and($history->meta['cockpit_page'])->toBeFalse();
});

it('exposes redacted provider responses for console consumers', function () {
    $deliveryId = feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusFailedFinal,
        channel: 'webhook',
        providerMessageId: 'webhook-failed-1',
        providerStatus: 'FAILED',
        providerPayload: [
            'status_code' => 500,
            'body' => ['message' => 'Endpoint failed'],
            'api_key' => 'secret-api-key',
            'nested' => ['token' => 'secret-token', 'safe' => 'visible'],
        ],
    );

    $response = app(FeedbackDeliveryConsoleContract::class)->providerResponse($deliveryId);

    expect($response)->toBeInstanceOf(FeedbackProviderResponseData::class)
        ->and($response->delivery_id)->toBe($deliveryId)
        ->and($response->provider_message_id)->toBe('webhook-failed-1')
        ->and($response->provider_status)->toBe('FAILED')
        ->and($response->payload['status_code'])->toBe(500)
        ->and($response->payload['api_key'])->toBe('[redacted]')
        ->and($response->payload['nested']['token'])->toBe('[redacted]')
        ->and($response->payload['nested']['safe'])->toBe('visible')
        ->and($response->meta['redacted'])->toBeTrue();
});

it('models retry requests as handoff facts without queueing retries or mutating delivery state', function () {
    $deliveryId = feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-retryable-1',
        meta: ['attempts' => 1, 'last_attempt_at' => '2026-07-02T01:00:00+08:00'],
    );

    $request = app(FeedbackDeliveryConsoleContract::class)->retryRequest(
        deliveryId: $deliveryId,
        requestedBy: 'operator-1',
        reason: 'manual retry requested',
        policy: new FeedbackRetryPolicyData(max_attempts: 3, stale_after_seconds: 300),
        now: '2026-07-02T01:01:00+08:00',
    );

    expect($request)->toBeInstanceOf(FeedbackDeliveryConsoleRetryRequestData::class)
        ->and($request->delivery_id)->toBe($deliveryId)
        ->and($request->requested_by)->toBe('operator-1')
        ->and($request->reason)->toBe('manual retry requested')
        ->and($request->eligible)->toBeTrue()
        ->and($request->decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationRetryable)
        ->and($request->meta['queues_retry'])->toBeFalse()
        ->and($request->meta['handoff_only'])->toBeTrue()
        ->and(FeedbackDeliveryRecord::query()->where('delivery_id', $deliveryId)->value('status'))->toBe(FeedbackDeliveryData::StatusFailedRetryable);
});

it('uses durable delivery fields when building retry request eligibility', function () {
    $deliveryId = feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-durable-console-retry',
        meta: ['idempotency_key' => 'sms-durable-console-retry', 'attempts' => 1],
    );

    feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-durable-console-retry',
        meta: ['idempotency_key' => 'sms-durable-console-retry', 'attempts' => 1],
    );
    feedbackConsoleRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-durable-console-retry',
        meta: ['idempotency_key' => 'sms-durable-console-retry', 'attempts' => 1],
    );

    $request = app(FeedbackDeliveryConsoleContract::class)->retryRequest(
        deliveryId: $deliveryId,
        requestedBy: 'operator-1',
        policy: new FeedbackRetryPolicyData(max_attempts: 3),
        now: '2026-07-02T01:01:00+08:00',
    );

    expect(FeedbackDeliveryRecord::query()->where('delivery_id', $deliveryId)->value('attempt_count'))->toBe(3)
        ->and($request->eligible)->toBeFalse()
        ->and($request->decision->attempts)->toBe(3)
        ->and($request->decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationExhausted);
});

it('models retry requests for final records as ineligible handoff facts', function () {
    $deliveryId = feedbackConsoleRecord(status: FeedbackDeliveryData::StatusDelivered, channel: 'email', providerMessageId: 'email-delivered-1');

    $request = app(FeedbackDeliveryConsoleContract::class)->retryRequest(
        deliveryId: $deliveryId,
        requestedBy: 'operator-1',
        reason: 'manual retry requested',
    );

    expect($request->eligible)->toBeFalse()
        ->and($request->decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationFinal)
        ->and($request->meta['queues_retry'])->toBeFalse();
});

it('binds the delivery console for package consumers', function () {
    expect(app(FeedbackDeliveryConsoleContract::class))->toBeInstanceOf(FeedbackDeliveryConsole::class)
        ->and(app(FeedbackDeliveryConsoleContract::class))->toBe(app(FeedbackDeliveryConsoleContract::class));
});

it('keeps delivery console API baseline independent from cockpit pages routes retry execution and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackConsoleRecord(
    string $status,
    string $channel,
    string $providerMessageId,
    string $providerStatus = 'ACCEPTED',
    array $providerPayload = [],
    string $correlationId = 'execution-1',
    array $meta = [],
): string {
    $records = app(FeedbackDeliveryAttemptRecorderContract::class)->record(new FeedbackDeliveryAttemptData(
        intent_key: 'claim.succeeded.claimant',
        receipts: [
            new FeedbackProviderReceiptData(
                intent_key: 'claim.succeeded.claimant',
                channel: $channel,
                recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test', phone: '+639171234567'),
                status: $status,
                provider_message_id: $providerMessageId,
                provider_status: $providerStatus,
                provider_payload: $providerPayload !== [] ? $providerPayload : ['provider' => $channel],
                correlation_id: $correlationId,
                causation_id: 'feedback-run-1',
                occurred_at: $meta['last_attempt_at'] ?? '2026-07-02T01:00:00+08:00',
                meta: $meta,
            ),
        ],
    ));

    return $records[0]->delivery_id;
}
