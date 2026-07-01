<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackOperationalChannelHealthData extends Data
{
    public function __construct(
        public string $channel,
        public bool $healthy,
        public string $status,
        public array $details = [],
        public array $meta = [],
    ) {}
}
