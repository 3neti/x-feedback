<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackProviderResponseData extends Data
{
    public function __construct(
        public string $delivery_id,
        public ?string $provider_message_id = null,
        public ?string $provider_status = null,
        public array $payload = [],
        public array $meta = [],
    ) {}
}
