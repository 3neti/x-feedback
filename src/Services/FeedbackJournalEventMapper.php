<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackJournalEventMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackJournalEventMapper implements FeedbackJournalEventMapperContract
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

    public function fromRecord(FeedbackDeliveryRecordData $record): FeedbackJournalEventData
    {
        return new FeedbackJournalEventData(
            event_name: $this->eventName($record->status),
            subject_id: $record->delivery_id ?: $this->subjectId($record->intent_key, $record->channel, $record->provider_message_id),
            correlation_id: $record->correlation_id,
            causation_id: $record->causation_id,
            references: $this->references(
                intentKey: $record->intent_key,
                channel: $record->channel,
                deliveryId: $record->delivery_id,
                providerMessageId: $record->provider_message_id,
                providerStatus: $record->provider_status,
                recipient: $record->recipient,
            ),
            payload: [
                'intent_key' => $record->intent_key,
                'channel' => $record->channel,
                'delivery_status' => $record->status,
                'provider_message_id' => $record->provider_message_id,
                'provider_status' => $record->provider_status,
                'recipient' => $this->recipientPayload($record->recipient),
                'record_meta' => $record->meta,
            ],
            meta: array_merge($record->meta, $this->meta()),
        );
    }

    public function fromReceipt(FeedbackProviderReceiptData $receipt): FeedbackJournalEventData
    {
        return new FeedbackJournalEventData(
            event_name: $this->eventName($receipt->status),
            subject_id: $this->subjectId($receipt->intent_key, $receipt->channel, $receipt->provider_message_id),
            correlation_id: $receipt->correlation_id,
            causation_id: $receipt->causation_id,
            references: $this->references(
                intentKey: $receipt->intent_key,
                channel: $receipt->channel,
                deliveryId: null,
                providerMessageId: $receipt->provider_message_id,
                providerStatus: $receipt->provider_status,
                recipient: $receipt->recipient,
            ),
            payload: [
                'intent_key' => $receipt->intent_key,
                'channel' => $receipt->channel,
                'delivery_status' => $receipt->status,
                'provider_message_id' => $receipt->provider_message_id,
                'provider_status' => $receipt->provider_status,
                'provider_payload' => $this->redact($receipt->provider_payload),
                'recipient' => $this->recipientPayload($receipt->recipient),
                'occurred_at' => $receipt->occurred_at,
                'receipt_meta' => $receipt->meta,
            ],
            meta: array_merge($receipt->meta, $this->meta(redacted: true)),
        );
    }

    public function fromRecords(array $records): array
    {
        return array_map(fn (FeedbackDeliveryRecordData $record): FeedbackJournalEventData => $this->fromRecord($record), $records);
    }

    private function eventName(string $status): string
    {
        return match ($status) {
            FeedbackDeliveryData::StatusPending,
            FeedbackDeliveryData::StatusQueued,
            FeedbackDeliveryData::StatusSending => FeedbackJournalEventData::EventCreated,
            FeedbackDeliveryData::StatusSent,
            FeedbackDeliveryData::StatusDelivered => FeedbackJournalEventData::EventSent,
            FeedbackDeliveryData::StatusExpired => FeedbackJournalEventData::EventExpired,
            default => FeedbackJournalEventData::EventFailed,
        };
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
        ?string $deliveryId,
        ?string $providerMessageId,
        ?string $providerStatus,
        FeedbackRecipientData $recipient,
    ): array {
        return [
            'intent_key' => $intentKey,
            'channel' => $channel,
            'delivery_id' => $deliveryId,
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

    private function meta(bool $redacted = false): array
    {
        return [
            'journal_ready' => true,
            'journal_handoff_only' => true,
            'canonical_source' => false,
            'x_journal_dependency' => false,
            'redacted' => $redacted,
        ];
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
