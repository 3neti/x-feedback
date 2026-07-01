<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRenderedArtifactData extends Data
{
    public function __construct(
        public string $type,
        public string $label,
        public string $channel,
        public string $strategy,
        public ?string $url = null,
        public ?string $preview = null,
        public ?string $attachment = null,
        public bool $hidden = false,
        public array $meta = [],
    ) {}
}
