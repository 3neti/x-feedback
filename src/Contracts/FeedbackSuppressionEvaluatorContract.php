<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionDecisionData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionPolicyData;

interface FeedbackSuppressionEvaluatorContract
{
    public function evaluate(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData|string $channel,
        ?FeedbackSuppressionPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackSuppressionDecisionData;
}
