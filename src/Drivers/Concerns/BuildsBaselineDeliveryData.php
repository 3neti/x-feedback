<?php

namespace LBHurtado\XFeedback\Drivers\Concerns;

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use Throwable;

trait BuildsBaselineDeliveryData
{
    protected function queuedBaselineDelivery(
        string $driver,
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
        array $result = [],
    ): FeedbackDeliveryData {
        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusQueued,
            result: [
                'driver' => $driver,
                ...$result,
            ],
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: [
                'feedback_only' => true,
                'provider_side_effect' => false,
                'baseline_driver' => true,
            ],
        );
    }

    protected function availableHealth(string $channel): FeedbackChannelHealthData
    {
        return new FeedbackChannelHealthData(
            channel: $channel,
            healthy: true,
            status: FeedbackChannelHealthData::StatusAvailable,
            details: [
                'baseline_driver' => true,
                'provider_side_effect' => false,
            ],
        );
    }

    protected function providerFailureDelivery(
        string $driver,
        string $transport,
        Throwable $exception,
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
        array $result = [],
    ): FeedbackDeliveryData {
        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusFailedRetryable,
            result: [
                'driver' => $driver,
                'transport' => $transport,
                ...$result,
            ],
            error: [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ],
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: [
                'feedback_only' => true,
                'provider_side_effect' => true,
                'provider_failure' => true,
                'retryable' => true,
                'exception_trace' => false,
            ],
        );
    }
}
