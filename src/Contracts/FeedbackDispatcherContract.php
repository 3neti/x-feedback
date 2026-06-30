<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackDispatcherContract
{
    /**
     * @return array<int, FeedbackDeliveryData>
     */
    public function dispatch(FeedbackIntentData $intent): array;
}
