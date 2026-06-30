<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Contracts\FeedbackNotificationRouteResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanItemData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

final class FeedbackDeliveryPlanner implements FeedbackDeliveryPlannerContract
{
    public function __construct(
        private readonly FeedbackChannelSelectorContract $selector,
        private readonly FeedbackNotificationRouteResolverContract $routeResolver,
    ) {}

    public function plan(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): FeedbackDeliveryPlanData
    {
        $channels = $this->selector->select($intent, $policy);
        $items = [];

        foreach ($intent->recipients as $recipient) {
            foreach ($channels as $channel) {
                $route = $this->routeResolver->resolve($recipient, $channel->key);

                $items[] = new FeedbackDeliveryPlanItemData(
                    intent_key: $intent->key,
                    recipient: $recipient,
                    channel: $channel->key,
                    priority: $channel->priority,
                    correlation_id: $intent->context?->correlation_id,
                    causation_id: $intent->context?->causation_id,
                    meta: [
                        'route' => $route?->address,
                        'notification_route' => $route?->toArray(),
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
}
