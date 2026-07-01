<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackFeatureProfileData extends Data
{
    public function __construct(
        public string $key,
        public ?string $name = null,
        public ?string $template_profile = null,
        public array $branding = [],
        public array $variables = [],
        public array $actions = [],
        public array $meta = [],
    ) {}
}
