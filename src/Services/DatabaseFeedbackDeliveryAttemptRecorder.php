<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Support\Str;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;

final class DatabaseFeedbackDeliveryAttemptRecorder implements FeedbackDeliveryAttemptRecorderContract
{
    public function record(FeedbackDeliveryAttemptData $attempt): array
    {
        return array_map(
            fn (FeedbackProviderReceiptData $receipt): FeedbackDeliveryRecordData => $this->recordReceipt($receipt),
            $attempt->receipts,
        );
    }

    public function all(): array
    {
        return FeedbackDeliveryRecord::query()
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData => $this->toData($record))
            ->all();
    }

    public function forCorrelation(string $correlationId): array
    {
        return FeedbackDeliveryRecord::query()
            ->where('correlation_id', $correlationId)
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData => $this->toData($record))
            ->all();
    }

    public function forIntent(string $intentKey): array
    {
        return FeedbackDeliveryRecord::query()
            ->where('intent_key', $intentKey)
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData => $this->toData($record))
            ->all();
    }

    public function reset(): void
    {
        FeedbackDeliveryRecord::query()->delete();
    }

    private function recordReceipt(FeedbackProviderReceiptData $receipt): FeedbackDeliveryRecordData
    {
        $idempotencyKey = $this->idempotencyKey($receipt);
        $record = FeedbackDeliveryRecord::query()->firstOrNew(['idempotency_key' => $idempotencyKey]);
        $attemptCount = ((int) $record->attempt_count) + 1;

        $record->fill([
            'delivery_id' => $record->delivery_id ?: (string) Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'intent_key' => $receipt->intent_key,
            'channel' => $receipt->channel,
            'recipient_type' => $receipt->recipient->type,
            'recipient_id' => $receipt->recipient->id,
            'recipient' => $receipt->recipient->toArray(),
            'status' => $receipt->status,
            'attempt_count' => $attemptCount,
            'max_attempts' => $this->integerMeta($receipt, 'max_attempts'),
            'provider_message_id' => $receipt->provider_message_id,
            'provider_status' => $receipt->provider_status,
            'provider_response' => $receipt->provider_payload,
            'correlation_id' => $receipt->correlation_id,
            'causation_id' => $receipt->causation_id,
            'last_attempted_at' => $receipt->occurred_at,
            'delivered_at' => $this->deliveredAt($receipt, $record),
            'failed_at' => $this->failedAt($receipt, $record),
            'expires_at' => $this->stringMeta($receipt, 'expires_at'),
            'meta' => [
                'receipt_meta' => $receipt->meta,
                'occurred_at' => $receipt->occurred_at,
                'non_canonical' => true,
                'audit_truth' => false,
            ],
        ]);

        $record->save();

        return $this->toData($record);
    }

    private function idempotencyKey(FeedbackProviderReceiptData $receipt): string
    {
        if (is_string($receipt->meta['idempotency_key'] ?? null) && trim($receipt->meta['idempotency_key']) !== '') {
            return trim($receipt->meta['idempotency_key']);
        }

        if ($receipt->provider_message_id !== null && trim($receipt->provider_message_id) !== '') {
            return sprintf('provider:%s:%s', $receipt->channel, trim($receipt->provider_message_id));
        }

        return hash('sha256', implode('|', [
            $receipt->intent_key,
            $receipt->channel,
            $receipt->recipient->type ?? '',
            $receipt->recipient->id ?? '',
            $receipt->correlation_id ?? '',
        ]));
    }

    private function deliveredAt(FeedbackProviderReceiptData $receipt, FeedbackDeliveryRecord $record): ?string
    {
        if ($record->delivered_at !== null) {
            return $record->delivered_at->toISOString();
        }

        return $receipt->status === 'delivered' ? $receipt->occurred_at : null;
    }

    private function failedAt(FeedbackProviderReceiptData $receipt, FeedbackDeliveryRecord $record): ?string
    {
        if ($record->failed_at !== null) {
            return $record->failed_at->toISOString();
        }

        return str_starts_with($receipt->status, 'failed') ? $receipt->occurred_at : null;
    }

    private function integerMeta(FeedbackProviderReceiptData $receipt, string $key): ?int
    {
        $value = $receipt->meta[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    private function stringMeta(FeedbackProviderReceiptData $receipt, string $key): ?string
    {
        $value = $receipt->meta[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function toData(FeedbackDeliveryRecord $record): FeedbackDeliveryRecordData
    {
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
            meta: (array) $record->meta,
        );
    }
}
