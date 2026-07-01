<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRenderedActionData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $target = null,
        public ?string $style = null,
        public string $channel = 'default',
        public string $render_as = FeedbackActionRenderingPolicyData::RenderAsButton,
        public bool $enabled = true,
        public array $meta = [],
    ) {}
}
