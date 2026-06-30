<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

final class FeedbackDispatcher implements FeedbackDispatcherContract
{
    public function __construct(
        private readonly FeedbackChannelRegistryContract $channels,
        private readonly FeedbackTemplateResolverContract $templates,
    ) {}

    /**
     * @return array<int, FeedbackDeliveryData>
     */
    public function dispatch(FeedbackIntentData $intent): array
    {
        $intent = $this->templates->resolve($intent);
        $deliveries = [];

        foreach ($intent->channels as $channel) {
            if (! $channel->enabled) {
                continue;
            }

            $driver = $this->channels->driver($channel->key);

            foreach ($intent->recipients as $recipient) {
                $deliveries[] = $driver->send($intent, $recipient, $channel);
            }
        }

        return $deliveries;
    }
}
