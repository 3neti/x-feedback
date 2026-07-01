<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Support\Collection;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackOperationalMonitorContract;
use LBHurtado\XFeedback\Contracts\FeedbackRetryFreshnessEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryFailureSummaryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackOperationalChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackOperationalMonitoringSnapshotData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryBacklogData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackChannelException;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;

final class FeedbackOperationalMonitor implements FeedbackOperationalMonitorContract
{
    /**
     * @param  array<int, string>  $defaultChannels
     */
    public function __construct(
        private readonly FeedbackChannelRegistryContract $channels,
        private readonly FeedbackRetryFreshnessEvaluatorContract $retryFreshness,
        private readonly array $defaultChannels = [],
    ) {}

    public function channelHealth(array $channels = []): array
    {
        return collect($channels !== [] ? $channels : $this->defaultChannels)
            ->values()
            ->map(fn (string $channel): FeedbackOperationalChannelHealthData => $this->healthForChannel($channel))
            ->all();
    }

    public function failureSummary(): FeedbackDeliveryFailureSummaryData
    {
        $records = $this->failedRecords();

        return new FeedbackDeliveryFailureSummaryData(
            total: $records->count(),
            by_status: $this->countsByStatus($records),
            by_channel: $this->countsBy($records, 'channel'),
            records: $records->values()->all(),
            meta: [
                'monitoring_only' => true,
                'lifecycle_truth' => false,
            ],
        );
    }

    public function retryBacklog(
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackRetryBacklogData {
        $decisions = $this->retryCandidateRecords()
            ->map(fn (FeedbackDeliveryRecordData $record): FeedbackRetryDecisionData => $this->retryFreshness->evaluateRecord($record, $policy, $now))
            ->filter(fn (FeedbackRetryDecisionData $decision): bool => in_array($decision->classification, [
                FeedbackRetryDecisionData::ClassificationRetryable,
                FeedbackRetryDecisionData::ClassificationExpired,
                FeedbackRetryDecisionData::ClassificationExhausted,
                FeedbackRetryDecisionData::ClassificationPending,
            ], true))
            ->values();

        return new FeedbackRetryBacklogData(
            total: $decisions->count(),
            retryable: $decisions->where('classification', FeedbackRetryDecisionData::ClassificationRetryable)->count(),
            expired: $decisions->where('classification', FeedbackRetryDecisionData::ClassificationExpired)->count(),
            exhausted: $decisions->where('classification', FeedbackRetryDecisionData::ClassificationExhausted)->count(),
            pending: $decisions->where('classification', FeedbackRetryDecisionData::ClassificationPending)->count(),
            decisions: $decisions->all(),
            meta: [
                'monitoring_only' => true,
                'queues_retry' => false,
                'mutates_delivery_status' => false,
            ],
        );
    }

    public function snapshot(
        array $channels = [],
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackOperationalMonitoringSnapshotData {
        return new FeedbackOperationalMonitoringSnapshotData(
            channels: $this->channelHealth($channels),
            failures: $this->failureSummary(),
            retry_backlog: $this->retryBacklog($policy, $now),
            meta: [
                'monitoring_only' => true,
                'alert_delivery_loop' => false,
                'cockpit_widget' => false,
            ],
        );
    }

    private function healthForChannel(string $channel): FeedbackOperationalChannelHealthData
    {
        try {
            $health = $this->channels->driver($channel)->health();
        } catch (UnknownFeedbackChannelException) {
            return new FeedbackOperationalChannelHealthData(
                channel: $channel,
                healthy: false,
                status: FeedbackChannelHealthData::StatusUnavailable,
                details: ['reason' => 'unknown_channel'],
                meta: ['monitoring_only' => true],
            );
        }

        return new FeedbackOperationalChannelHealthData(
            channel: $health->channel,
            healthy: $health->healthy,
            status: $health->status,
            details: $health->details,
            meta: ['monitoring_only' => true],
        );
    }

    /**
     * @return Collection<int, FeedbackDeliveryRecordData>
     */
    private function failedRecords(): Collection
    {
        return FeedbackDeliveryRecord::query()
            ->whereIn('status', [
                FeedbackDeliveryData::StatusFailedRetryable,
                FeedbackDeliveryData::StatusFailedFinal,
                FeedbackDeliveryData::StatusExpired,
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData => $this->toData($record));
    }

    /**
     * @return Collection<int, FeedbackDeliveryRecordData>
     */
    private function retryCandidateRecords(): Collection
    {
        return FeedbackDeliveryRecord::query()
            ->whereIn('status', [
                FeedbackDeliveryData::StatusPending,
                FeedbackDeliveryData::StatusQueued,
                FeedbackDeliveryData::StatusSending,
                FeedbackDeliveryData::StatusSent,
                FeedbackDeliveryData::StatusFailedRetryable,
                FeedbackDeliveryData::StatusRetryScheduled,
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData => $this->toData($record));
    }

    /**
     * @param  Collection<int, FeedbackDeliveryRecordData>  $records
     * @return array<string, int>
     */
    private function countsBy(Collection $records, string $property): array
    {
        return $records
            ->countBy(fn (FeedbackDeliveryRecordData $record): string => (string) $record->{$property})
            ->sortKeys()
            ->all();
    }

    /**
     * @param  Collection<int, FeedbackDeliveryRecordData>  $records
     * @return array<string, int>
     */
    private function countsByStatus(Collection $records): array
    {
        $counts = $records->countBy(fn (FeedbackDeliveryRecordData $record): string => $record->status);

        return collect([
            FeedbackDeliveryData::StatusFailedRetryable,
            FeedbackDeliveryData::StatusFailedFinal,
            FeedbackDeliveryData::StatusExpired,
        ])
            ->filter(fn (string $status): bool => $counts->has($status))
            ->mapWithKeys(fn (string $status): array => [$status => $counts->get($status)])
            ->all();
    }

    private function toData(FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData
    {
        $meta = (array) $record->meta;
        $receiptMeta = (array) ($meta['receipt_meta'] ?? []);

        return new FeedbackDeliveryRecordData(
            intent_key: $record->intent_key,
            channel: $record->channel,
            recipient: new FeedbackRecipientData(...(array) $record->recipient),
            status: $record->status,
            delivery_id: $record->delivery_id,
            idempotency_key: $record->idempotency_key,
            attempt_count: (int) $record->attempt_count,
            max_attempts: $record->max_attempts,
            provider_message_id: $record->provider_message_id,
            provider_status: $record->provider_status,
            provider_response: (array) $record->provider_response,
            correlation_id: $record->correlation_id,
            causation_id: $record->causation_id,
            last_attempted_at: $record->last_attempted_at?->toISOString(),
            delivered_at: $record->delivered_at?->toISOString(),
            failed_at: $record->failed_at?->toISOString(),
            expires_at: $record->expires_at?->toISOString(),
            in_app_state: $record->in_app_state,
            read_at: $record->read_at?->toISOString(),
            archived_at: $record->archived_at?->toISOString(),
            dismissed_at: $record->dismissed_at?->toISOString(),
            meta: array_merge($receiptMeta, $meta),
        );
    }
}
