<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRetryPolicyData extends Data
{
    /**
     * @param  array<int, string>  $retryable_statuses
     * @param  array<int, string>  $final_statuses
     * @param  array<int, int>  $backoff_seconds
     */
    public function __construct(
        public int $max_attempts = 3,
        public array $retryable_statuses = [FeedbackDeliveryData::StatusFailedRetryable],
        public array $final_statuses = [
            FeedbackDeliveryData::StatusDelivered,
            FeedbackDeliveryData::StatusFailedFinal,
            FeedbackDeliveryData::StatusExpired,
            FeedbackDeliveryData::StatusCancelled,
        ],
        public int $stale_after_seconds = 900,
        public array $backoff_seconds = [60, 300, 900],
        public array $meta = [],
    ) {}
}
