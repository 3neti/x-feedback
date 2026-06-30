<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Support\Str;
use LBHurtado\XFeedback\Contracts\FeedbackWebhookSenderContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackWebhookMessageData;
use LBHurtado\XFeedback\Data\FeedbackWebhookSendResultData;
use Spatie\WebhookServer\WebhookCall;

final class SpatieFeedbackWebhookSender implements FeedbackWebhookSenderContract
{
    public function send(FeedbackWebhookMessageData $message): FeedbackWebhookSendResultData
    {
        $messageId = $message->message_id ?? (string) Str::uuid();

        $webhook = WebhookCall::create()
            ->uuid($messageId)
            ->url($message->url)
            ->payload($message->payload)
            ->withHeaders($message->headers)
            ->meta($message->meta);

        if ($message->secret !== null && $message->secret !== '') {
            $webhook->useSecret($message->secret);
        } else {
            $webhook->doNotSign();
        }

        $webhook->dispatch();

        return new FeedbackWebhookSendResultData(
            message_id: $messageId,
            status: FeedbackDeliveryData::StatusQueued,
            result: [
                'transport' => 'spatie_webhook_server',
                'url' => $message->url,
            ],
        );
    }
}
