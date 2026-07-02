<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryConsoleHistoryData extends Data
{
    /**
     * @param  array<int, FeedbackDeliveryConsoleRecordData>  $records
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $total,
        public array $records = [],
        public array $filters = [],
        public array $meta = [],
    ) {}
}
