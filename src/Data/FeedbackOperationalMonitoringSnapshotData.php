<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackOperationalMonitoringSnapshotData extends Data
{
    /**
     * @param  array<int, FeedbackOperationalChannelHealthData>  $channels
     */
    public function __construct(
        public array $channels,
        public FeedbackDeliveryFailureSummaryData $failures,
        public FeedbackRetryBacklogData $retry_backlog,
        public array $meta = [],
    ) {}
}
