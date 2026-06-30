<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackTemplateResolverContract
{
    public function resolve(FeedbackIntentData $intent): FeedbackIntentData;
}
