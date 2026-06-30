<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackEventMapperContract
{
    public function map(FeedbackEventData $event): FeedbackIntentData;
}
