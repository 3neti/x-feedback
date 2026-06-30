<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackProviderReceiptData extends Data
{
    public function __construct(
        public string $intent_key,
        public string $channel,
        public FeedbackRecipientData $recipient,
        public string $status,
        public ?string $provider_message_id = null,
        public ?string $provider_status = null,
        public array $provider_payload = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public ?string $occurred_at = null,
        public array $meta = [],
    ) {}
}
