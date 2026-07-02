<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryConsoleRecordData extends Data
{
    public function __construct(
        public string $delivery_id,
        public string $intent_key,
        public string $channel,
        public FeedbackRecipientData $recipient,
        public string $status,
        public int $attempt_count = 0,
        public ?int $max_attempts = null,
        public ?string $provider_message_id = null,
        public ?string $provider_status = null,
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public ?string $last_attempted_at = null,
        public ?string $delivered_at = null,
        public ?string $failed_at = null,
        public ?string $expires_at = null,
        public ?string $in_app_state = null,
        public array $meta = [],
    ) {}
}
