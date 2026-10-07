<?php

declare(strict_types=1);

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;

interface FeedbackChannelContentRendererContract
{
    public function decision(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
    ): FeedbackRenderingDecisionData;

    public function text(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
    ): string;
}
