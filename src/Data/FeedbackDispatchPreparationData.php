<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDispatchPreparationData extends Data
{
    public function __construct(
        public string $intent_key,
        public FeedbackIntentData $intent,
        public FeedbackDeliveryPlanData $plan,
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $meta = [],
    ) {}
}
