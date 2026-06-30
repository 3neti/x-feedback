<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackJournalReceiptData extends Data
{
    public function __construct(
        public string $event_type,
        public string $source = 'x-feedback',
        public string $subject_type = 'feedback_delivery',
        public string|int|null $subject_id = null,
        public ?string $actor_type = 'system',
        public string|int|null $actor_id = 'x-feedback',
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $references = [],
        public array $payload = [],
        public array $meta = [],
    ) {}
}
