<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Database\Eloquent\Builder;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryConsoleContract;
use LBHurtado\XFeedback\Contracts\FeedbackRetryFreshnessEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackProviderResponseData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackDeliveryRecordException;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;

final class FeedbackDeliveryConsole implements FeedbackDeliveryConsoleContract
{
    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'api_key',
        'apikey',
        'authorization',
        'client_secret',
        'password',
        'secret',
        'signature',
        'token',
    ];

    public function __construct(
        private readonly FeedbackRetryFreshnessEvaluatorContract $retryFreshness,
    ) {}

    public function status(string $deliveryId): FeedbackDeliveryConsoleRecordData
    {
        return $this->toConsoleRecord($this->record($deliveryId));
    }

    public function history(array $filters = []): FeedbackDeliveryConsoleHistoryData
    {
        $records = $this->filteredQuery($filters)
            ->orderByDesc('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryConsoleRecordData => $this->toConsoleRecord($record))
            ->all();

        return new FeedbackDeliveryConsoleHistoryData(
            total: count($records),
            records: $records,
            filters: $this->recognizedFilters($filters),
            meta: [
                'console_read_only' => true,
                'cockpit_page' => false,
                'lifecycle_truth' => false,
            ],
        );
    }

    public function providerResponse(string $deliveryId): FeedbackProviderResponseData
    {
        $record = $this->record($deliveryId);

        return new FeedbackProviderResponseData(
            delivery_id: (string) $record->delivery_id,
            provider_message_id: $record->provider_message_id,
            provider_status: $record->provider_status,
            payload: $this->redact((array) $record->provider_response),
            meta: [
                'console_read_only' => true,
                'redacted' => true,
                'credential_source' => false,
            ],
        );
    }

    public function retryRequest(
        string $deliveryId,
        string $requestedBy,
        ?string $reason = null,
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackDeliveryConsoleRetryRequestData {
        $record = $this->record($deliveryId);
        $decision = $this->retryFreshness->evaluateRecord($this->toRecordData($record), $policy, $now);

        return new FeedbackDeliveryConsoleRetryRequestData(
            delivery_id: (string) $record->delivery_id,
            requested_by: $requestedBy,
            eligible: $decision->should_retry,
            decision: $decision,
            reason: $reason,
            meta: [
                'handoff_only' => true,
                'queues_retry' => false,
                'mutates_delivery_status' => false,
                'workflow_authorization' => false,
            ],
        );
    }

    private function record(string $deliveryId): FeedbackDeliveryRecord
    {
        $record = FeedbackDeliveryRecord::query()
            ->where('delivery_id', $deliveryId)
            ->first();

        if (! $record instanceof FeedbackDeliveryRecord) {
            throw UnknownFeedbackDeliveryRecordException::forDeliveryId($deliveryId);
        }

        return $record;
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = FeedbackDeliveryRecord::query();

        foreach ($this->recognizedFilters($filters) as $key => $value) {
            $query->where($key, $value);
        }

        return $query;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function recognizedFilters(array $filters): array
    {
        return collect($filters)
            ->only(['correlation_id', 'intent_key', 'channel', 'status', 'recipient_type', 'recipient_id'])
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->all();
    }

    private function toConsoleRecord(FeedbackDeliveryRecord $record): FeedbackDeliveryConsoleRecordData
    {
        return new FeedbackDeliveryConsoleRecordData(
            delivery_id: (string) $record->delivery_id,
            intent_key: $record->intent_key,
            channel: $record->channel,
            recipient: new FeedbackRecipientData(...(array) $record->recipient),
            status: $record->status,
            attempt_count: (int) $record->attempt_count,
            max_attempts: $record->max_attempts,
            provider_message_id: $record->provider_message_id,
            provider_status: $record->provider_status,
            correlation_id: $record->correlation_id,
            causation_id: $record->causation_id,
            last_attempted_at: $record->last_attempted_at?->toISOString(),
            delivered_at: $record->delivered_at?->toISOString(),
            failed_at: $record->failed_at?->toISOString(),
            expires_at: $record->expires_at?->toISOString(),
            in_app_state: $record->in_app_state,
            meta: [
                'console_read_only' => true,
                'communication_delivery_state' => true,
                'lifecycle_truth' => false,
            ],
        );
    }

    private function toRecordData(FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData
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

    private function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $this->sensitiveKeys, true)) {
                $payload[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }
}
