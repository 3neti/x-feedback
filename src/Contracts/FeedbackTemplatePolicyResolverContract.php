<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackFeatureProfileData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackTemplatePolicyResolverContract
{
    public function featureProfileFor(FeedbackIntentData $intent): ?FeedbackFeatureProfileData;

    /**
     * @return array<int, string|null>
     */
    public function profileCandidatesFor(FeedbackIntentData $intent): array;

    /**
     * @return array<int, string|null>
     */
    public function channelCandidatesFor(FeedbackIntentData $intent): array;

    public function variablesFor(FeedbackIntentData $intent): array;

    public function actionsFor(FeedbackIntentData $intent): array;
}
