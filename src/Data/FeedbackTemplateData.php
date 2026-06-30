<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackTemplateData extends Data
{
    public function __construct(
        public string $key,
        public string $title,
        public string $body,
        public ?string $summary = null,
        public ?string $locale = null,
        public ?string $profile = null,
        public ?string $channel = null,
        public array $variables = [],
        public array $actions = [],
        public array $artifacts = [],
        public array $meta = [],
    ) {}
}
