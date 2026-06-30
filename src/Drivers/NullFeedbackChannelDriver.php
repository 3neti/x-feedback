<?php

namespace LBHurtado\XFeedback\Drivers;

use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class NullFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusSent,
            result: [
                'driver' => 'null',
                'message' => 'suppressed',
            ],
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: [
                'feedback_only' => true,
            ],
        );
    }
}
