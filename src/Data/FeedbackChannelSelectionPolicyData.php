<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackChannelSelectionPolicyData extends Data
{
    /**
     * @param  array<int, string>  $allowed_channels
     * @param  array<int, string>  $preferred_channels
     * @param  array<int, string>  $fallback_channels
     * @param  array<int, string>  $required_channels
     * @param  array<int, string>  $disabled_channels
     */
    public function __construct(
        public array $allowed_channels = [],
        public array $preferred_channels = [],
        public array $fallback_channels = [],
        public array $required_channels = [],
        public array $disabled_channels = [],
        public ?string $profile = null,
        public ?string $locale = null,
        public array $meta = [],
    ) {}
}
