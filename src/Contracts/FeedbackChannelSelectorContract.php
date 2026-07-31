<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackChannelSelectorContract
{
    /**
     * @return array<int, FeedbackChannelData>
     */
    public function select(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): array;
}
