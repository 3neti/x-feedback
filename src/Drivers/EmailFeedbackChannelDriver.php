<?php

namespace LBHurtado\XFeedback\Drivers;

use Illuminate\Support\Facades\Mail;
use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\Concerns\BuildsBaselineDeliveryData;
use LBHurtado\XFeedback\Mail\FeedbackEmailMessage;
use Throwable;

final class EmailFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    use BuildsBaselineDeliveryData;

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        try {
            Mail::to($recipient->email)->send(new FeedbackEmailMessage($intent, $recipient, $channel));
        } catch (Throwable $exception) {
            return $this->providerFailureDelivery(
                driver: 'email',
                transport: 'laravel_mail',
                exception: $exception,
                intent: $intent,
                recipient: $recipient,
                channel: $channel,
            );
        }

        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusSent,
            result: [
                'driver' => 'email',
                'transport' => 'laravel_mail',
            ],
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: [
                'feedback_only' => true,
                'provider_side_effect' => true,
            ],
        );
    }

    public function supports(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): bool {
        return $channel->key === 'email' && $recipient->email !== null && $recipient->email !== '';
    }

    public function health(): FeedbackChannelHealthData
    {
        return $this->availableHealth('email');
    }
}
