<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryConsoleRetryRequestData extends Data
{
    public function __construct(
        public string $delivery_id,
        public string $requested_by,
        public bool $eligible,
        public FeedbackRetryDecisionData $decision,
        public ?string $reason = null,
        public array $meta = [],
    ) {}
}
