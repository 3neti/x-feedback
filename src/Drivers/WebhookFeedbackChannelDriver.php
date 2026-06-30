<?php

namespace LBHurtado\XFeedback\Drivers;

use LBHurtado\XFeedback\Contracts\FeedbackWebhookSenderContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackWebhookMessageData;
use LBHurtado\XFeedback\Drivers\Concerns\BuildsBaselineDeliveryData;

final class WebhookFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    use BuildsBaselineDeliveryData;

    public function __construct(
        private readonly FeedbackWebhookSenderContract $sender,
    ) {}

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        $url = (string) $channel->options['url'];
        $result = $this->sender->send(new FeedbackWebhookMessageData(
            url: $url,
            payload: $this->payload($intent, $recipient, $channel),
            headers: (array) ($channel->options['headers'] ?? []),
            secret: isset($channel->options['secret']) && is_string($channel->options['secret'])
                ? $channel->options['secret']
                : null,
            meta: [
                'source' => 'x-feedback',
                'intent_key' => $intent->key,
                'correlation_id' => $intent->context?->correlation_id,
                'causation_id' => $intent->context?->causation_id,
            ],
        ));

        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: $result->status,
            provider_message_id: $result->message_id,
            result: [
                'driver' => 'webhook',
                ...$result->result,
                'url' => $url,
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
        return $channel->key === 'webhook'
            && isset($channel->options['url'])
            && is_string($channel->options['url'])
            && $channel->options['url'] !== '';
    }

    public function health(): FeedbackChannelHealthData
    {
        return $this->availableHealth('webhook');
    }

    private function payload(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): array {
        return [
            'intent_key' => $intent->key,
            'event_type' => $intent->context?->event_type,
            'message' => [
                'title' => $intent->message->title,
                'body' => $intent->message->body,
            ],
            'recipient' => [
                'type' => $recipient->type,
                'id' => $recipient->id,
                'email' => $recipient->email,
                'phone' => $recipient->phone,
            ],
            'channel' => [
                'key' => $channel->key,
                'options' => $channel->options,
            ],
            'correlation_id' => $intent->context?->correlation_id,
            'causation_id' => $intent->context?->causation_id,
            'subject_type' => $intent->context?->subject_type,
            'subject_id' => $intent->context?->subject_id,
        ];
    }
}
