<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryAttemptData extends Data
{
    public const string StatusCompleted = 'completed';

    /**
     * @param  array<int, FeedbackDeliveryData>  $deliveries
     * @param  array<int, FeedbackProviderReceiptData>  $receipts
     */
    public function __construct(
        public string $intent_key,
        public string $status = self::StatusCompleted,
        public array $deliveries = [],
        public array $receipts = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $meta = [],
    ) {}
}
