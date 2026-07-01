<?php

use Illuminate\Support\Facades\Schema;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;
use LBHurtado\XFeedback\Services\DatabaseFeedbackDeliveryAttemptRecorder;

it('loads the durable feedback delivery records migration', function () {
    expect(Schema::hasTable('feedback_delivery_records'))->toBeTrue()
        ->and(Schema::hasColumns('feedback_delivery_records', [
            'delivery_id',
            'idempotency_key',
            'intent_key',
            'channel',
            'recipient_type',
            'recipient_id',
            'recipient',
            'status',
            'attempt_count',
            'max_attempts',
            'provider_message_id',
            'provider_status',
            'provider_response',
            'correlation_id',
            'causation_id',
            'last_attempted_at',
            'delivered_at',
            'failed_at',
            'expires_at',
            'meta',
        ]))->toBeTrue();
});

it('binds the delivery attempt recorder to the durable database implementation', function () {
    expect(app(FeedbackDeliveryAttemptRecorderContract::class))->toBeInstanceOf(DatabaseFeedbackDeliveryAttemptRecorder::class)
        ->and(app(FeedbackDeliveryAttemptRecorderContract::class))->toBe(app(FeedbackDeliveryAttemptRecorderContract::class));
});

it('persists delivery attempt receipts as communication delivery state', function () {
    $records = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackDurableDeliveryAttempt());

    expect($records)->toHaveCount(1)
        ->and($records[0]->intent_key)->toBe('claim.succeeded.claimant')
        ->and($records[0]->channel)->toBe('sms')
        ->and($records[0]->recipient->phone)->toBe('+639171234567')
        ->and($records[0]->status)->toBe('sent')
        ->and($records[0]->provider_message_id)->toBe('sms-provider-1')
        ->and($records[0]->provider_status)->toBe('ACCEPTED')
        ->and($records[0]->correlation_id)->toBe('execution-1')
        ->and($records[0]->causation_id)->toBe('feedback-run-1')
        ->and($records[0]->attempt_count)->toBe(1)
        ->and($records[0]->idempotency_key)->toBe('provider:sms:sms-provider-1')
        ->and($records[0]->meta['non_canonical'])->toBeTrue();

    $model = FeedbackDeliveryRecord::query()->firstOrFail();

    expect($model->intent_key)->toBe('claim.succeeded.claimant')
        ->and($model->recipient['phone'])->toBe('+639171234567')
        ->and($model->provider_response)->toBe(['provider' => 'sms', 'accepted' => true])
        ->and($model->attempt_count)->toBe(1)
        ->and($model->delivered_at)->toBeNull()
        ->and($model->failed_at)->toBeNull();
});

it('updates the same durable delivery record for repeated attempts with the same idempotency key', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);

    $recorder->record(feedbackDurableDeliveryAttempt(status: 'failed_retryable', providerStatus: 'TEMPORARY_FAILURE'));
    $records = $recorder->record(feedbackDurableDeliveryAttempt(status: 'sent', providerStatus: 'ACCEPTED'));

    expect(FeedbackDeliveryRecord::query()->count())->toBe(1)
        ->and($records[0]->status)->toBe('sent')
        ->and($records[0]->provider_status)->toBe('ACCEPTED')
        ->and($records[0]->attempt_count)->toBe(2)
        ->and($records[0]->failed_at)->not->toBeNull();
});

it('records terminal delivery timestamps without deciding settlement or lifecycle truth', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);

    $delivered = $recorder->record(feedbackDurableDeliveryAttempt(status: 'delivered', providerMessageId: 'delivered-1'))[0];
    $failed = $recorder->record(feedbackDurableDeliveryAttempt(status: 'failed_final', providerMessageId: 'failed-1'))[0];

    expect($delivered->delivered_at)->not->toBeNull()
        ->and($delivered->meta)->not->toHaveKey('settlement_status')
        ->and($failed->failed_at)->not->toBeNull()
        ->and($failed->meta)->not->toHaveKey('claim_status');
});

it('keeps durable delivery records queryable by correlation and intent', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);

    $recorder->record(feedbackDurableDeliveryAttempt());

    expect($recorder->forCorrelation('execution-1'))->toHaveCount(1)
        ->and($recorder->forIntent('claim.succeeded.claimant'))->toHaveCount(1)
        ->and($recorder->all())->toHaveCount(1);
});

it('can reset durable delivery records for test and package baseline isolation', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);

    $attempt = feedbackDurableDeliveryAttempt();
    $recorder->record($attempt);
    $recorder->reset();

    expect($recorder->all())->toBe([])
        ->and(FeedbackDeliveryRecord::query()->count())->toBe(0)
        ->and($attempt->receipts)->toHaveCount(1);
});

it('keeps durable delivery records independent from journal audit truth routes jobs controllers and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackDurableDeliveryAttempt(
    string $status = 'sent',
    string $providerStatus = 'ACCEPTED',
    string $providerMessageId = 'sms-provider-1',
): FeedbackDeliveryAttemptData {
    return new FeedbackDeliveryAttemptData(
        intent_key: 'claim.succeeded.claimant',
        receipts: [
            new FeedbackProviderReceiptData(
                intent_key: 'claim.succeeded.claimant',
                channel: 'sms',
                recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
                status: $status,
                provider_message_id: $providerMessageId,
                provider_status: $providerStatus,
                provider_payload: ['provider' => 'sms', 'accepted' => $providerStatus === 'ACCEPTED'],
                correlation_id: 'execution-1',
                causation_id: 'feedback-run-1',
                occurred_at: '2026-07-01T00:00:00+00:00',
                meta: ['max_attempts' => 3, 'expires_at' => '2026-07-02T00:00:00+00:00'],
            ),
        ],
        correlation_id: 'execution-1',
        causation_id: 'feedback-run-1',
    );
}
