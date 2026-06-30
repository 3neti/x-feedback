<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRuntimeContract;
use LBHurtado\XFeedback\Contracts\FeedbackReceiptHandoffMapperContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDispatchPreparationData;

final class FeedbackDeliveryAttemptRuntime implements FeedbackDeliveryAttemptRuntimeContract
{
    public function __construct(
        private readonly FeedbackChannelRegistryContract $channels,
        private readonly FeedbackReceiptHandoffMapperContract $receipts,
    ) {}

    public function execute(FeedbackDispatchPreparationData $preparation): FeedbackDeliveryAttemptData
    {
        $deliveries = [];
        $receipts = [];

        foreach ($preparation->plan->items as $item) {
            $channel = $this->channelFor($preparation, $item->channel);
            $driver = $this->channels->driver($channel->key);
            $delivery = $driver->send($preparation->intent, $item->recipient, $channel);

            $deliveries[] = $delivery;
            $receipts[] = $this->receipts->fromDelivery($delivery);
        }

        return new FeedbackDeliveryAttemptData(
            intent_key: $preparation->intent_key,
            deliveries: $deliveries,
            receipts: $receipts,
            correlation_id: $preparation->correlation_id,
            causation_id: $preparation->causation_id,
            meta: [
                'planned_items' => count($preparation->plan->items),
                'delivered_items' => count($deliveries),
            ],
        );
    }

    private function channelFor(FeedbackDispatchPreparationData $preparation, string $channelKey): FeedbackChannelData
    {
        foreach ($preparation->intent->channels as $channel) {
            if ($channel->key === $channelKey) {
                return $channel;
            }
        }

        return new FeedbackChannelData(key: $channelKey);
    }
}
