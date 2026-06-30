<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;

interface FeedbackRetryFreshnessEvaluatorContract
{
    public function evaluateRecord(
        FeedbackDeliveryRecordData $record,
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackRetryDecisionData;
}
