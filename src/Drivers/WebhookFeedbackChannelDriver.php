<?php

namespace LBHurtado\XFeedback\Drivers;

use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\Concerns\BuildsBaselineDeliveryData;

final class WebhookFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    use BuildsBaselineDeliveryData;

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        return $this->queuedBaselineDelivery('webhook', $intent, $recipient, $channel, [
            'message' => 'webhook delivery prepared',
            'url' => $channel->options['url'] ?? null,
        ]);
    }

    public function supports(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): bool {
        return $channel->key === 'webhook'
            && isset($channel->options['url'])
            && is_string($channel->options['url'])
            && $channel->options['url'] !== '';
    }

    public function health(): FeedbackChannelHealthData
    {
        return $this->availableHealth('webhook');
    }
}
