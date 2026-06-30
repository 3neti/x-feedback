<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackChannelSelectorContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

final class FeedbackChannelSelector implements FeedbackChannelSelectorContract
{
    public function select(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): array
    {
        $policy ??= new FeedbackChannelSelectionPolicyData;

        $channels = array_values(array_filter(
            $intent->channels,
            fn (FeedbackChannelData $channel): bool => $this->isSelectable($channel, $policy),
        ));

        usort($channels, fn (FeedbackChannelData $left, FeedbackChannelData $right): int => $this->sort($left, $right, $policy));

        return $channels;
    }

    private function isSelectable(FeedbackChannelData $channel, FeedbackChannelSelectionPolicyData $policy): bool
    {
        if (! $channel->enabled) {
            return false;
        }

        if (in_array($channel->key, $policy->disabled_channels, true)) {
            return false;
        }

        if ($policy->allowed_channels !== [] && ! in_array($channel->key, $policy->allowed_channels, true)) {
            return false;
        }

        return true;
    }

    private function sort(FeedbackChannelData $left, FeedbackChannelData $right, FeedbackChannelSelectionPolicyData $policy): int
    {
        return [$this->bucket($left, $policy), $left->priority, $left->key]
            <=> [$this->bucket($right, $policy), $right->priority, $right->key];
    }

    private function bucket(FeedbackChannelData $channel, FeedbackChannelSelectionPolicyData $policy): int
    {
        if (in_array($channel->key, $policy->required_channels, true)) {
            return 0;
        }

        if (in_array($channel->key, $policy->preferred_channels, true)) {
            return 1;
        }

        if (in_array($channel->key, $policy->fallback_channels, true)) {
            return 3;
        }

        return 2;
    }
}
