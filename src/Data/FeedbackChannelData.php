<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackChannelData extends Data
{
    public function __construct(
        public string $key,
        public bool $enabled = true,
        public int $priority = 100,
        public array $options = [],
        public array $meta = [],
    ) {}
}
