<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackWebhookMessageData extends Data
{
    public function __construct(
        public string $url,
        public array $payload,
        public array $headers = [],
        public ?string $secret = null,
        public ?string $message_id = null,
        public array $meta = [],
    ) {}
}
