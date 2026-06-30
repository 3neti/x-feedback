<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDispatchPreparationData;

interface FeedbackDeliveryAttemptRuntimeContract
{
    public function execute(FeedbackDispatchPreparationData $preparation): FeedbackDeliveryAttemptData;
}
