<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackJournalReceiptMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalReceiptData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackJournalReceiptMapper implements FeedbackJournalReceiptMapperContract
{
    public function fromRecord(FeedbackDeliveryRecordData $record): FeedbackJournalReceiptData
    {
        return new FeedbackJournalReceiptData(
            event_type: $this->eventType($record->status),
            subject_id: $this->subjectId($record->intent_key, $record->channel, $record->provider_message_id),
            correlation_id: $record->correlation_id,
            causation_id: $record->causation_id,
            references: $this->references(
                intentKey: $record->intent_key,
                channel: $record->channel,
                providerMessageId: $record->provider_message_id,
                providerStatus: $record->provider_status,
                recipient: $record->recipient,
            ),
            payload: [
                'intent_key' => $record->intent_key,
                'channel' => $record->channel,
                'status' => $record->status,
                'provider_message_id' => $record->provider_message_id,
                'provider_status' => $record->provider_status,
                'recipient' => $this->recipientPayload($record->recipient),
                'record_meta' => $record->meta,
            ],
            meta: array_merge($record->meta, [
                'journal_ready' => true,
                'canonical_source' => false,
            ]),
        );
    }

    public function fromReceipt(FeedbackProviderReceiptData $receipt): FeedbackJournalReceiptData
    {
        return new FeedbackJournalReceiptData(
            event_type: $this->eventType($receipt->status),
            subject_id: $this->subjectId($receipt->intent_key, $receipt->channel, $receipt->provider_message_id),
            correlation_id: $receipt->correlation_id,
            causation_id: $receipt->causation_id,
            references: $this->references(
                intentKey: $receipt->intent_key,
                channel: $receipt->channel,
                providerMessageId: $receipt->provider_message_id,
                providerStatus: $receipt->provider_status,
                recipient: $receipt->recipient,
            ),
            payload: [
                'intent_key' => $receipt->intent_key,
                'channel' => $receipt->channel,
                'status' => $receipt->status,
                'provider_message_id' => $receipt->provider_message_id,
                'provider_status' => $receipt->provider_status,
                'provider_payload' => $receipt->provider_payload,
                'recipient' => $this->recipientPayload($receipt->recipient),
                'occurred_at' => $receipt->occurred_at,
                'receipt_meta' => $receipt->meta,
            ],
            meta: array_merge($receipt->meta, [
                'journal_ready' => true,
                'canonical_source' => false,
            ]),
        );
    }

    public function fromRecords(array $records): array
    {
        return array_map(fn (FeedbackDeliveryRecordData $record): FeedbackJournalReceiptData => $this->fromRecord($record), $records);
    }

    private function eventType(string $status): string
    {
        return 'feedback.delivery.'.$status;
    }

    private function subjectId(string $intentKey, string $channel, ?string $providerMessageId): string
    {
        return implode(':', [
            $intentKey,
            $channel,
            $providerMessageId ?: 'unassigned',
        ]);
    }

    private function references(
        string $intentKey,
        string $channel,
        ?string $providerMessageId,
        ?string $providerStatus,
        FeedbackRecipientData $recipient,
    ): array {
        return [
            'intent_key' => $intentKey,
            'channel' => $channel,
            'provider_message_id' => $providerMessageId,
            'provider_status' => $providerStatus,
            'recipient_type' => $recipient->type,
            'recipient_id' => $recipient->id,
        ];
    }

    private function recipientPayload(FeedbackRecipientData $recipient): array
    {
        return [
            'type' => $recipient->type,
            'id' => $recipient->id,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'phone' => $recipient->phone,
            'routes' => $recipient->routes,
            'meta' => $recipient->meta,
        ];
    }
}
