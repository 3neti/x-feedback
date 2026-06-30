<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

final class PassthroughFeedbackTemplateResolver implements FeedbackTemplateResolverContract
{
    public function resolve(FeedbackIntentData $intent): FeedbackIntentData
    {
        return $intent;
    }
}
