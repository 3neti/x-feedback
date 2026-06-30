<?php

namespace LBHurtado\XFeedback\Drivers;

use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\Concerns\BuildsBaselineDeliveryData;

final class MailFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    use BuildsBaselineDeliveryData;

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        return $this->queuedBaselineDelivery('mail', $intent, $recipient, $channel, [
            'message' => 'mail delivery prepared',
        ]);
    }

    public function supports(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): bool {
        return $channel->key === 'mail' && $recipient->email !== null && $recipient->email !== '';
    }

    public function health(): FeedbackChannelHealthData
    {
        return $this->availableHealth('mail');
    }
}
