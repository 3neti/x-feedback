<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryFailureSummaryData extends Data
{
    /**
     * @param  array<string, int>  $by_status
     * @param  array<string, int>  $by_channel
     * @param  array<int, FeedbackDeliveryRecordData>  $records
     */
    public function __construct(
        public int $total,
        public array $by_status = [],
        public array $by_channel = [],
        public array $records = [],
        public array $meta = [],
    ) {}
}
