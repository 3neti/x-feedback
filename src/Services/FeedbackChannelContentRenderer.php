<?php

declare(strict_types=1);

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackActionArtifactRendererContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelContentRendererContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;

final readonly class FeedbackChannelContentRenderer implements FeedbackChannelContentRendererContract
{
    public function __construct(
        private FeedbackActionArtifactRendererContract $renderer,
    ) {}

    public function decision(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
    ): FeedbackRenderingDecisionData {
        return $this->renderer->render($intent, $channel);
    }

    public function text(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
    ): string {
        $decision = $this->decision($intent, $channel);
        $lines = [trim($intent->message->body)];

        foreach ($decision->actions as $action) {
            if (! $action->enabled || $action->target === null) {
                continue;
            }

            $lines[] = sprintf('%s: %s', $action->label, $action->target);
        }

        return implode("\n", array_filter($lines));
    }
}
