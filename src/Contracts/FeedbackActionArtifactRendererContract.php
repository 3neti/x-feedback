<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackActionRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackArtifactRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;

interface FeedbackActionArtifactRendererContract
{
    public function render(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
        ?FeedbackActionRenderingPolicyData $actionPolicy = null,
        ?FeedbackArtifactRenderingPolicyData $artifactPolicy = null,
    ): FeedbackRenderingDecisionData;
}
