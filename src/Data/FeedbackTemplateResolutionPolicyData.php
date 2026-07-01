<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackTemplateResolutionPolicyData extends Data
{
    /**
     * @param  array<string, array<int, string>>  $profile_fallbacks
     * @param  array<string, array<int, string>>  $channel_fallbacks
     * @param  array<int, FeedbackFeatureProfileData>  $feature_profiles
     */
    public function __construct(
        public string $default_profile = 'default',
        public array $profile_fallbacks = [],
        public array $channel_fallbacks = [],
        public array $feature_profiles = [],
        public array $meta = [],
    ) {}
}
