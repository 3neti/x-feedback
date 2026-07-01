<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRenderingDecisionData extends Data
{
    /**
     * @param  array<int, FeedbackRenderedActionData>  $actions
     * @param  array<int, FeedbackRenderedArtifactData>  $artifacts
     */
    public function __construct(
        public string $intent_key,
        public string $channel,
        public array $actions = [],
        public array $artifacts = [],
        public array $meta = [],
    ) {}
}
