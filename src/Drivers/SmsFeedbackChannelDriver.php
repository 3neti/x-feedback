<?php

namespace LBHurtado\XFeedback\Drivers;

use LBHurtado\SMS\Facades\SMS;
use LBHurtado\XFeedback\Contracts\FeedbackChannelContentRendererContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\Concerns\BuildsBaselineDeliveryData;
use Throwable;

final class SmsFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    use BuildsBaselineDeliveryData;

    public function __construct(
        private readonly FeedbackChannelContentRendererContract $renderer,
    ) {}

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        $smsDriver = $channel->options['driver'] ?? config('x-feedback.transports.sms.driver', 'engagespark');
        $sender = $channel->options['sender'] ?? config('x-feedback.transports.sms.sender', 'XCHANGE');

        try {
            $result = SMS::channel($smsDriver)
                ->from($sender)
                ->to($recipient->phone)
                ->content($this->renderer->text($intent, $channel))
                ->send();
        } catch (Throwable $exception) {
            return $this->providerFailureDelivery(
                driver: 'sms',
                transport: 'lbhurtado_sms',
                exception: $exception,
                intent: $intent,
                recipient: $recipient,
                channel: $channel,
                result: [
                    'sms_driver' => $smsDriver,
                    'sender' => $sender,
                ],
            );
        }

        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusSent,
            provider_message_id: $this->providerMessageId($result),
            result: [
                'driver' => 'sms',
                'transport' => 'lbhurtado_sms',
                'sms_driver' => $smsDriver,
                'sender' => $sender,
                'provider_result' => $result,
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
        return $channel->key === 'sms' && $recipient->phone !== null && $recipient->phone !== '';
    }

    public function health(): FeedbackChannelHealthData
    {
        return $this->availableHealth('sms');
    }

    private function providerMessageId(mixed $result): ?string
    {
        if (is_array($result)) {
            $messageId = $result['message_id'] ?? $result['id'] ?? null;

            return is_scalar($messageId) ? (string) $messageId : null;
        }

        if (is_object($result) && isset($result->message_id) && is_scalar($result->message_id)) {
            return (string) $result->message_id;
        }

        return null;
    }
}
