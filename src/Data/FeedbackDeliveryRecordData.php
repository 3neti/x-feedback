<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryRecordData extends Data
{
    public function __construct(
        public string $intent_key,
        public string $channel,
        public FeedbackRecipientData $recipient,
        public string $status,
        public ?string $delivery_id = null,
        public ?string $idempotency_key = null,
        public int $attempt_count = 0,
        public ?int $max_attempts = null,
        public ?string $provider_message_id = null,
        public ?string $provider_status = null,
        public array $provider_response = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public ?string $last_attempted_at = null,
        public ?string $delivered_at = null,
        public ?string $failed_at = null,
        public ?string $expires_at = null,
        public ?string $in_app_state = null,
        public ?string $read_at = null,
        public ?string $archived_at = null,
        public ?string $dismissed_at = null,
        public array $meta = [],
    ) {}
}
