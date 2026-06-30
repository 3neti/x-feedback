<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackNotificationPreferenceData extends Data
{
    public function __construct(
        public string $event_key,
        public string $channel,
        public bool $enabled = true,
        public ?string $recipient_type = null,
        public string|int|null $recipient_id = null,
        public array $meta = [],
    ) {}
}
