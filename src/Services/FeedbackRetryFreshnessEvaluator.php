<?php

namespace LBHurtado\XFeedback\Services;

use DateTimeImmutable;
use LBHurtado\XFeedback\Contracts\FeedbackRetryFreshnessEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;

final class FeedbackRetryFreshnessEvaluator implements FeedbackRetryFreshnessEvaluatorContract
{
    public function evaluateRecord(
        FeedbackDeliveryRecordData $record,
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackRetryDecisionData {
        $policy ??= new FeedbackRetryPolicyData;
        $attempts = $this->attempts($record);

        if (in_array($record->status, $policy->final_statuses, true)) {
            return $this->decision(FeedbackRetryDecisionData::ClassificationFinal, false, false, $attempts, null, 'final_status', $record);
        }

        if ($this->isStale($record, $policy, $now)) {
            return $this->decision(FeedbackRetryDecisionData::ClassificationExpired, false, true, $attempts, null, 'stale', $record);
        }

        if (in_array($record->status, $policy->retryable_statuses, true)) {
            if ($attempts >= $policy->max_attempts) {
                return $this->decision(FeedbackRetryDecisionData::ClassificationExhausted, false, false, $attempts, null, 'max_attempts', $record);
            }

            return $this->decision(
                FeedbackRetryDecisionData::ClassificationRetryable,
                true,
                false,
                $attempts,
                $this->nextRetryAt($record, $policy, $attempts, $now),
                'retryable_status',
                $record,
            );
        }

        return $this->decision(FeedbackRetryDecisionData::ClassificationPending, false, false, $attempts, null, 'pending_status', $record);
    }

    private function decision(
        string $classification,
        bool $shouldRetry,
        bool $shouldExpire,
        int $attempts,
        ?string $nextRetryAt,
        string $reason,
        FeedbackDeliveryRecordData $record,
    ): FeedbackRetryDecisionData {
        return new FeedbackRetryDecisionData(
            classification: $classification,
            should_retry: $shouldRetry,
            should_expire: $shouldExpire,
            attempts: $attempts,
            next_retry_at: $nextRetryAt,
            reason: $reason,
            meta: [
                'intent_key' => $record->intent_key,
                'channel' => $record->channel,
                'status' => $record->status,
                'provider_message_id' => $record->provider_message_id,
            ],
        );
    }

    private function attempts(FeedbackDeliveryRecordData $record): int
    {
        $attempts = $record->meta['attempts'] ?? 0;

        return is_numeric($attempts) ? max(0, (int) $attempts) : 0;
    }

    private function isStale(FeedbackDeliveryRecordData $record, FeedbackRetryPolicyData $policy, ?string $now): bool
    {
        $lastAttemptAt = $this->lastAttemptAt($record);

        if ($lastAttemptAt === null) {
            return false;
        }

        return $this->timestamp($now) - $lastAttemptAt->getTimestamp() > $policy->stale_after_seconds;
    }

    private function nextRetryAt(FeedbackDeliveryRecordData $record, FeedbackRetryPolicyData $policy, int $attempts, ?string $now): ?string
    {
        $lastAttemptAt = $this->lastAttemptAt($record) ?? new DateTimeImmutable($now ?? 'now');
        $backoff = $policy->backoff_seconds[$attempts] ?? end($policy->backoff_seconds) ?: 0;

        return $lastAttemptAt->modify('+'.$backoff.' seconds')->format(DATE_ATOM);
    }

    private function lastAttemptAt(FeedbackDeliveryRecordData $record): ?DateTimeImmutable
    {
        $value = $record->meta['last_attempt_at'] ?? null;

        return is_string($value) && $value !== '' ? new DateTimeImmutable($value) : null;
    }

    private function timestamp(?string $now): int
    {
        return (new DateTimeImmutable($now ?? 'now'))->getTimestamp();
    }
}
