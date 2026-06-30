<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackProviderCallbackData extends Data
{
    public function __construct(
        public string $provider,
        public string $channel,
        public string $intent_key,
        public ?string $provider_message_id = null,
        public ?string $provider_status = null,
        public ?string $status = null,
        public ?FeedbackRecipientData $recipient = null,
        public array $payload = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public ?string $occurred_at = null,
        public array $meta = [],
    ) {}
}
