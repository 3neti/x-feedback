<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryPlanData extends Data
{
    /**
     * @param  array<int, FeedbackDeliveryPlanItemData>  $items
     */
    public function __construct(
        public string $intent_key,
        public array $items = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $meta = [],
    ) {}
}
