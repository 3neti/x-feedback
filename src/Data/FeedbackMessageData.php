<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackMessageData extends Data
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $summary = null,
        public ?string $locale = null,
        public ?string $template = null,
        public array $variables = [],
        public array $actions = [],
        public array $artifacts = [],
        public array $meta = [],
    ) {}
}
