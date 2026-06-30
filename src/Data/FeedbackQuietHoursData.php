<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackQuietHoursData extends Data
{
    /**
     * @param  array<int, string>  $channels
     * @param  array<int, string>  $recipient_types
     */
    public function __construct(
        public string $start_time,
        public string $end_time,
        public string $timezone = 'UTC',
        public array $channels = [],
        public array $recipient_types = [],
        public array $meta = [],
    ) {}
}
