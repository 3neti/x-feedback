<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderCallbackData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

interface FeedbackProviderCallbackMapperContract
{
    public function toReceipt(FeedbackProviderCallbackData $callback): FeedbackProviderReceiptData;

    public function toEvent(FeedbackProviderCallbackData $callback): FeedbackEventData;
}
