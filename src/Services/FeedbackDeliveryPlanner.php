<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanItemData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackDeliveryPlanner implements FeedbackDeliveryPlannerContract
{
    public function __construct(
        private readonly FeedbackChannelSelectorContract $selector,
    ) {}

    public function plan(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): FeedbackDeliveryPlanData
    {
        $channels = $this->selector->select($intent, $policy);
        $items = [];

        foreach ($intent->recipients as $recipient) {
            foreach ($channels as $channel) {
                $items[] = new FeedbackDeliveryPlanItemData(
                    intent_key: $intent->key,
                    recipient: $recipient,
                    channel: $channel->key,
                    priority: $channel->priority,
                    correlation_id: $intent->context?->correlation_id,
                    causation_id: $intent->context?->causation_id,
                    meta: [
                        'route' => $this->routeFor($recipient, $channel->key),
                        'channel_options' => $channel->options,
                        'channel_meta' => $channel->meta,
                    ],
                );
            }
        }

        return new FeedbackDeliveryPlanData(
            intent_key: $intent->key,
            items: $items,
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: [
                'event_type' => $intent->context?->event_type,
                'source' => $intent->context?->source,
                'subject_type' => $intent->context?->subject_type,
                'subject_id' => $intent->context?->subject_id,
            ],
        );
    }

    private function routeFor(FeedbackRecipientData $recipient, string $channel): mixed
    {
        return $recipient->routeFor($channel);
    }
}
