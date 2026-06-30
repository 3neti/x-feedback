<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryPlanData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackDeliveryPlannerContract
{
    public function plan(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): FeedbackDeliveryPlanData;
}
