<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackReceiptHandoffMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

final class FeedbackReceiptHandoffMapper implements FeedbackReceiptHandoffMapperContract
{
    public function fromDelivery(FeedbackDeliveryData $delivery, array $providerPayload = [], ?string $occurredAt = null): FeedbackProviderReceiptData
    {
        return new FeedbackProviderReceiptData(
            intent_key: $delivery->intent_key,
            channel: $delivery->channel,
            recipient: $delivery->recipient,
            status: $delivery->status,
            provider_message_id: $delivery->provider_message_id,
            provider_status: $this->providerStatus($delivery),
            provider_payload: $providerPayload,
            correlation_id: $delivery->correlation_id,
            causation_id: $delivery->causation_id,
            occurred_at: $occurredAt,
            meta: [
                'delivery_result' => $delivery->result,
                'delivery_error' => $delivery->error,
                'delivery_meta' => $delivery->meta,
            ],
        );
    }

    private function providerStatus(FeedbackDeliveryData $delivery): ?string
    {
        $status = $delivery->result['provider_status'] ?? null;

        return is_scalar($status) && $status !== '' ? (string) $status : null;
    }
}
