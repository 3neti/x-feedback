<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackProviderCallbackMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderCallbackData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackProviderCallbackMapper implements FeedbackProviderCallbackMapperContract
{
    public function toReceipt(FeedbackProviderCallbackData $callback): FeedbackProviderReceiptData
    {
        return new FeedbackProviderReceiptData(
            intent_key: $callback->intent_key,
            channel: $callback->channel,
            recipient: $this->recipient($callback),
            status: $this->status($callback),
            provider_message_id: $callback->provider_message_id,
            provider_status: $callback->provider_status,
            provider_payload: $callback->payload,
            correlation_id: $callback->correlation_id,
            causation_id: $callback->causation_id,
            occurred_at: $callback->occurred_at,
            meta: array_merge($callback->meta, [
                'provider' => $callback->provider,
                'callback_mapped' => true,
            ]),
        );
    }

    public function toEvent(FeedbackProviderCallbackData $callback): FeedbackEventData
    {
        $status = $this->status($callback);

        return new FeedbackEventData(
            type: 'feedback.provider_callback.'.$status,
            source: $callback->provider,
            payload: [
                'provider' => $callback->provider,
                'channel' => $callback->channel,
                'intent_key' => $callback->intent_key,
                'provider_message_id' => $callback->provider_message_id,
                'provider_status' => $callback->provider_status,
                'status' => $status,
                'recipient' => $this->recipientPayload($this->recipient($callback)),
                'provider_payload' => $callback->payload,
            ],
            correlation_id: $callback->correlation_id,
            causation_id: $callback->causation_id,
            subject_id: $this->subjectId($callback),
            subject_type: 'feedback_delivery',
            actor_id: $callback->provider,
            actor_type: 'provider',
            occurred_at: $callback->occurred_at,
            meta: $callback->meta,
        );
    }

    private function status(FeedbackProviderCallbackData $callback): string
    {
        if ($callback->status !== null && $callback->status !== '') {
            return $callback->status;
        }

        return match (strtolower((string) $callback->provider_status)) {
            'delivered', 'delivery_success', 'success' => FeedbackDeliveryData::StatusDelivered,
            'bounced', 'failed', 'failure', 'undeliverable' => FeedbackDeliveryData::StatusFailedFinal,
            'accepted', 'sent', 'queued' => FeedbackDeliveryData::StatusSent,
            default => FeedbackDeliveryData::StatusPending,
        };
    }

    private function recipient(FeedbackProviderCallbackData $callback): FeedbackRecipientData
    {
        return $callback->recipient ?? new FeedbackRecipientData(type: 'unknown');
    }

    private function subjectId(FeedbackProviderCallbackData $callback): string
    {
        return implode(':', [
            $callback->intent_key,
            $callback->channel,
            $callback->provider_message_id ?: 'unassigned',
        ]);
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
