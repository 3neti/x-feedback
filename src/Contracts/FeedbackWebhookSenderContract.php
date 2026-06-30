<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackWebhookMessageData;
use LBHurtado\XFeedback\Data\FeedbackWebhookSendResultData;

interface FeedbackWebhookSenderContract
{
    public function send(FeedbackWebhookMessageData $message): FeedbackWebhookSendResultData;
}
