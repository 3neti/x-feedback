<?php

use LBHurtado\XFeedback\Contracts\FeedbackRetryFreshnessEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;
use LBHurtado\XFeedback\Services\FeedbackRetryFreshnessEvaluator;

it('models retry and freshness policies without queue or persistence concerns', function () {
    $policy = new FeedbackRetryPolicyData(
        max_attempts: 3,
        retryable_statuses: [FeedbackDeliveryData::StatusFailedRetryable],
        final_statuses: [FeedbackDeliveryData::StatusDelivered, FeedbackDeliveryData::StatusFailedFinal],
        stale_after_seconds: 300,
        backoff_seconds: [60, 300, 900],
        meta: ['profile' => 'default'],
    );

    expect($policy->max_attempts)->toBe(3)
        ->and($policy->retryable_statuses)->toBe([FeedbackDeliveryData::StatusFailedRetryable])
        ->and($policy->final_statuses)->toBe([FeedbackDeliveryData::StatusDelivered, FeedbackDeliveryData::StatusFailedFinal])
        ->and($policy->stale_after_seconds)->toBe(300)
        ->and($policy->backoff_seconds)->toBe([60, 300, 900])
        ->and($policy->meta)->toBe(['profile' => 'default']);
});

it('classifies retryable delivery records and calculates the next retry timestamp', function () {
    $decision = app(FeedbackRetryFreshnessEvaluatorContract::class)->evaluateRecord(
        record: feedbackRetryRecord(
            status: FeedbackDeliveryData::StatusFailedRetryable,
            meta: ['attempts' => 1, 'last_attempt_at' => '2026-06-30T12:00:00+08:00'],
        ),
        policy: new FeedbackRetryPolicyData(backoff_seconds: [60, 300, 900]),
        now: '2026-06-30T12:01:00+08:00',
    );

    expect($decision)->toBeInstanceOf(FeedbackRetryDecisionData::class)
        ->and($decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationRetryable)
        ->and($decision->should_retry)->toBeTrue()
        ->and($decision->should_expire)->toBeFalse()
        ->and($decision->attempts)->toBe(1)
        ->and($decision->next_retry_at)->toBe('2026-06-30T12:05:00+08:00')
        ->and($decision->reason)->toBe('retryable_status');
});

it('classifies final delivery records as terminal and does not retry', function () {
    $decision = app(FeedbackRetryFreshnessEvaluatorContract::class)->evaluateRecord(
        record: feedbackRetryRecord(status: FeedbackDeliveryData::StatusDelivered, meta: ['attempts' => 1]),
        policy: new FeedbackRetryPolicyData,
        now: '2026-06-30T12:01:00+08:00',
    );

    expect($decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationFinal)
        ->and($decision->should_retry)->toBeFalse()
        ->and($decision->reason)->toBe('final_status');
});

it('classifies stale non-terminal delivery records as expired without queueing retries', function () {
    $decision = app(FeedbackRetryFreshnessEvaluatorContract::class)->evaluateRecord(
        record: feedbackRetryRecord(
            status: FeedbackDeliveryData::StatusPending,
            meta: ['attempts' => 0, 'last_attempt_at' => '2026-06-30T12:00:00+08:00'],
        ),
        policy: new FeedbackRetryPolicyData(stale_after_seconds: 300),
        now: '2026-06-30T12:06:00+08:00',
    );

    expect($decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationExpired)
        ->and($decision->should_retry)->toBeFalse()
        ->and($decision->should_expire)->toBeTrue()
        ->and($decision->reason)->toBe('stale');
});

it('classifies records beyond max attempts as exhausted', function () {
    $decision = app(FeedbackRetryFreshnessEvaluatorContract::class)->evaluateRecord(
        record: feedbackRetryRecord(
            status: FeedbackDeliveryData::StatusFailedRetryable,
            meta: ['attempts' => 3, 'last_attempt_at' => '2026-06-30T12:00:00+08:00'],
        ),
        policy: new FeedbackRetryPolicyData(max_attempts: 3),
        now: '2026-06-30T12:01:00+08:00',
    );

    expect($decision->classification)->toBe(FeedbackRetryDecisionData::ClassificationExhausted)
        ->and($decision->should_retry)->toBeFalse()
        ->and($decision->reason)->toBe('max_attempts');
});

it('binds the retry freshness evaluator for package consumers', function () {
    expect(app(FeedbackRetryFreshnessEvaluatorContract::class))->toBeInstanceOf(FeedbackRetryFreshnessEvaluator::class)
        ->and(app(FeedbackRetryFreshnessEvaluatorContract::class))->toBe(app(FeedbackRetryFreshnessEvaluatorContract::class));
});

it('keeps retry and freshness policy independent from queues persistence routes providers and host packages', function () {
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

function feedbackRetryRecord(string $status, array $meta = []): FeedbackDeliveryRecordData
{
    return new FeedbackDeliveryRecordData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        status: $status,
        provider_message_id: 'provider-message-1',
        provider_status: strtoupper($status),
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        meta: $meta,
    );
}
