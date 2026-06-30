<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackChannelSelectorContract
{
    /**
     * @return array<int, \LBHurtado\XFeedback\Data\FeedbackChannelData>
     */
    public function select(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): array;
}
