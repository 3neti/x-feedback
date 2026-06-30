<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDispatchPreparationData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackDispatchPreparerContract
{
    public function prepare(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): FeedbackDispatchPreparationData;
}
