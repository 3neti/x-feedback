<?php

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackOperationalMonitorContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryFailureSummaryData;
use LBHurtado\XFeedback\Data\FeedbackOperationalMonitoringSnapshotData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryBacklogData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;
use LBHurtado\XFeedback\Services\FeedbackOperationalMonitor;

it('aggregates channel health from registered drivers without sending feedback', function () {
    $health = app(FeedbackOperationalMonitorContract::class)->channelHealth(['null', 'log', 'in_app']);

    expect($health)->toHaveCount(3)
        ->and($health[0]->channel)->toBe('null')
        ->and($health[0]->healthy)->toBeTrue()
        ->and($health[0]->meta['monitoring_only'])->toBeTrue()
        ->and($health[1]->channel)->toBe('log')
        ->and($health[2]->channel)->toBe('in_app')
        ->and(FeedbackDeliveryRecord::query()->count())->toBe(0);
});

it('represents unknown monitored channels as unavailable read models', function () {
    $health = app(FeedbackOperationalMonitorContract::class)->channelHealth(['imaginary']);

    expect($health)->toHaveCount(1)
        ->and($health[0]->channel)->toBe('imaginary')
        ->and($health[0]->healthy)->toBeFalse()
        ->and($health[0]->status)->toBe('unavailable')
        ->and($health[0]->details['reason'])->toBe('unknown_channel')
        ->and($health[0]->meta['monitoring_only'])->toBeTrue();
});

it('summarizes failed delivery records by status and channel', function () {
    feedbackOperationalRecord(status: FeedbackDeliveryData::StatusFailedRetryable, channel: 'sms', providerMessageId: 'sms-1');
    feedbackOperationalRecord(status: FeedbackDeliveryData::StatusFailedFinal, channel: 'mail', providerMessageId: 'mail-1');
    feedbackOperationalRecord(status: FeedbackDeliveryData::StatusExpired, channel: 'webhook', providerMessageId: 'webhook-1');
    feedbackOperationalRecord(status: FeedbackDeliveryData::StatusDelivered, channel: 'in_app', providerMessageId: 'in-app-1');

    $summary = app(FeedbackOperationalMonitorContract::class)->failureSummary();

    expect($summary)->toBeInstanceOf(FeedbackDeliveryFailureSummaryData::class)
        ->and($summary->total)->toBe(3)
        ->and($summary->by_status)->toBe([
            FeedbackDeliveryData::StatusFailedRetryable => 1,
            FeedbackDeliveryData::StatusFailedFinal => 1,
            FeedbackDeliveryData::StatusExpired => 1,
        ])
        ->and($summary->by_channel)->toBe([
            'mail' => 1,
            'sms' => 1,
            'webhook' => 1,
        ])
        ->and($summary->records)->toHaveCount(3)
        ->and($summary->records[0]->status)->toBe(FeedbackDeliveryData::StatusFailedRetryable);
});

it('builds a retry backlog from delivery records without queueing retries or mutating status', function () {
    feedbackOperationalRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-retryable',
        meta: ['attempts' => 1, 'last_attempt_at' => '2026-07-01T01:05:00+08:00'],
    );
    feedbackOperationalRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'webhook',
        providerMessageId: 'webhook-exhausted',
        meta: ['attempts' => 3, 'last_attempt_at' => '2026-07-01T01:05:00+08:00'],
    );
    feedbackOperationalRecord(
        status: FeedbackDeliveryData::StatusPending,
        channel: 'mail',
        providerMessageId: 'mail-expired',
        meta: ['attempts' => 0, 'last_attempt_at' => '2026-07-01T00:00:00+08:00'],
    );

    $backlog = app(FeedbackOperationalMonitorContract::class)->retryBacklog(
        policy: new FeedbackRetryPolicyData(max_attempts: 3, stale_after_seconds: 300),
        now: '2026-07-01T01:06:00+08:00',
    );

    expect($backlog)->toBeInstanceOf(FeedbackRetryBacklogData::class)
        ->and($backlog->total)->toBe(3)
        ->and($backlog->retryable)->toBe(1)
        ->and($backlog->exhausted)->toBe(1)
        ->and($backlog->expired)->toBe(1)
        ->and($backlog->pending)->toBe(0)
        ->and($backlog->decisions)->toHaveCount(3)
        ->and($backlog->decisions[0])->toBeInstanceOf(FeedbackRetryDecisionData::class)
        ->and(FeedbackDeliveryRecord::query()->where('provider_message_id', 'sms-retryable')->value('status'))->toBe(FeedbackDeliveryData::StatusFailedRetryable);
});

it('builds an operational snapshot from health failures and retry backlog', function () {
    feedbackOperationalRecord(
        status: FeedbackDeliveryData::StatusFailedRetryable,
        channel: 'sms',
        providerMessageId: 'sms-1',
        meta: ['attempts' => 1, 'last_attempt_at' => '2026-07-01T01:00:00+08:00'],
    );

    $snapshot = app(FeedbackOperationalMonitorContract::class)->snapshot(
        channels: ['null', 'imaginary'],
        policy: new FeedbackRetryPolicyData(stale_after_seconds: 300),
        now: '2026-07-01T01:01:00+08:00',
    );

    expect($snapshot)->toBeInstanceOf(FeedbackOperationalMonitoringSnapshotData::class)
        ->and($snapshot->channels)->toHaveCount(2)
        ->and($snapshot->channels[1]->healthy)->toBeFalse()
        ->and($snapshot->failures->total)->toBe(1)
        ->and($snapshot->retry_backlog->retryable)->toBe(1)
        ->and($snapshot->meta['monitoring_only'])->toBeTrue()
        ->and($snapshot->meta)->not->toHaveKey('lifecycle_status');
});

it('binds the operational monitor for package consumers', function () {
    expect(app(FeedbackOperationalMonitorContract::class))->toBeInstanceOf(FeedbackOperationalMonitor::class)
        ->and(app(FeedbackOperationalMonitorContract::class))->toBe(app(FeedbackOperationalMonitorContract::class));
});

it('keeps operational monitoring independent from alert loops cockpit routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackOperationalRecord(
    string $status,
    string $channel,
    string $providerMessageId,
    array $meta = [],
): void {
    app(FeedbackDeliveryAttemptRecorderContract::class)->record(new FeedbackDeliveryAttemptData(
        intent_key: 'claim.succeeded.claimant',
        receipts: [
            new FeedbackProviderReceiptData(
                intent_key: 'claim.succeeded.claimant',
                channel: $channel,
                recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test', phone: '+639171234567'),
                status: $status,
                provider_message_id: $providerMessageId,
                provider_status: strtoupper($status),
                provider_payload: ['provider' => $channel],
                correlation_id: 'execution-1',
                causation_id: 'feedback-run-1',
                occurred_at: $meta['last_attempt_at'] ?? '2026-07-01T01:00:00+08:00',
                meta: $meta,
            ),
        ],
    ));
}
