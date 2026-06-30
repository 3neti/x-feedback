<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

final class InMemoryFeedbackDeliveryAttemptRecorder implements FeedbackDeliveryAttemptRecorderContract
{
    /**
     * @var array<int, FeedbackDeliveryRecordData>
     */
    private array $records = [];

    public function record(FeedbackDeliveryAttemptData $attempt): array
    {
        $records = array_map(
            fn (FeedbackProviderReceiptData $receipt): FeedbackDeliveryRecordData => $this->recordFromReceipt($receipt),
            $attempt->receipts,
        );

        array_push($this->records, ...$records);

        return $records;
    }

    public function all(): array
    {
        return $this->records;
    }

    public function forCorrelation(string $correlationId): array
    {
        return array_values(array_filter(
            $this->records,
            fn (FeedbackDeliveryRecordData $record): bool => $record->correlation_id === $correlationId,
        ));
    }

    public function forIntent(string $intentKey): array
    {
        return array_values(array_filter(
            $this->records,
            fn (FeedbackDeliveryRecordData $record): bool => $record->intent_key === $intentKey,
        ));
    }

    public function reset(): void
    {
        $this->records = [];
    }

    private function recordFromReceipt(FeedbackProviderReceiptData $receipt): FeedbackDeliveryRecordData
    {
        return new FeedbackDeliveryRecordData(
            intent_key: $receipt->intent_key,
            channel: $receipt->channel,
            recipient: $receipt->recipient,
            status: $receipt->status,
            provider_message_id: $receipt->provider_message_id,
            provider_status: $receipt->provider_status,
            correlation_id: $receipt->correlation_id,
            causation_id: $receipt->causation_id,
            meta: [
                'provider_payload' => $receipt->provider_payload,
                'receipt_meta' => $receipt->meta,
                'occurred_at' => $receipt->occurred_at,
                'non_canonical' => true,
            ],
        );
    }
}
