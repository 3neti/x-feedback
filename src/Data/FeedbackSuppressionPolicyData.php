<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackSuppressionPolicyData extends Data
{
    /**
     * @param  array<int, FeedbackNotificationPreferenceData>  $preferences
     * @param  array<int, FeedbackQuietHoursData>  $quiet_hours
     * @param  array<int, string>  $opt_out_channels
     * @param  array<int, string>  $disabled_channels
     * @param  array<int, string>  $required_channels
     */
    public function __construct(
        public array $preferences = [],
        public array $quiet_hours = [],
        public array $opt_out_channels = [],
        public array $disabled_channels = [],
        public array $required_channels = [],
        public ?int $stale_after_seconds = null,
        public array $meta = [],
    ) {}
}
