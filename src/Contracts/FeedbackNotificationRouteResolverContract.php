<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackNotificationRouteData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

interface FeedbackNotificationRouteResolverContract
{
    public function resolve(FeedbackRecipientData $recipient, string $channel): ?FeedbackNotificationRouteData;

    /**
     * @return array<int, FeedbackNotificationRouteData>
     */
    public function resolveAll(FeedbackRecipientData $recipient, string $channel): array;
}
