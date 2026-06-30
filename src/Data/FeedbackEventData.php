<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackEventData extends Data
{
    public function __construct(
        public string $type,
        public ?string $source = null,
        public array $payload = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public string|int|null $subject_id = null,
        public ?string $subject_type = null,
        public string|int|null $actor_id = null,
        public ?string $actor_type = null,
        public ?string $occurred_at = null,
        public array $meta = [],
    ) {}
}
