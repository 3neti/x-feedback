<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRetryBacklogData extends Data
{
    /**
     * @param  array<int, FeedbackRetryDecisionData>  $decisions
     */
    public function __construct(
        public int $total,
        public int $retryable = 0,
        public int $expired = 0,
        public int $exhausted = 0,
        public int $pending = 0,
        public array $decisions = [],
        public array $meta = [],
    ) {}
}
