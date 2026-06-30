<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackNotificationRouteData extends Data
{
    public function __construct(
        public string $notifiable_type,
        public string|int|null $notifiable_id,
        public string $channel,
        public string $address,
        public ?string $verified_at = null,
        public bool $is_primary = false,
        public int $priority = 100,
        public array $meta = [],
    ) {}
}
