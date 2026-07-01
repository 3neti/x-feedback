<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackTemplatePolicyResolverContract;
use LBHurtado\XFeedback\Data\FeedbackFeatureProfileData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackTemplateResolutionPolicyData;

final class FeedbackTemplatePolicyResolver implements FeedbackTemplatePolicyResolverContract
{
    public function __construct(
        private readonly FeedbackTemplateResolutionPolicyData $policy = new FeedbackTemplateResolutionPolicyData,
    ) {}

    public function featureProfileFor(FeedbackIntentData $intent): ?FeedbackFeatureProfileData
    {
        $requested = $this->requestedProfile($intent);

        if ($requested === null) {
            return null;
        }

        foreach ($this->policy->feature_profiles as $profile) {
            if ($profile instanceof FeedbackFeatureProfileData && $profile->key === $requested) {
                return $profile;
            }
        }

        return null;
    }

    public function profileCandidatesFor(FeedbackIntentData $intent): array
    {
        $requested = $this->requestedProfile($intent);
        $featureProfile = $this->featureProfileFor($intent);
        $candidates = [];

        if ($featureProfile?->template_profile !== null) {
            $candidates[] = $featureProfile->template_profile;
        }

        if ($requested !== null && $featureProfile?->template_profile === null) {
            $candidates[] = $requested;
        }

        if ($requested !== null) {
            foreach ($this->policy->profile_fallbacks[$requested] ?? [] as $fallback) {
                $candidates[] = $fallback;
            }
        }

        $candidates[] = $this->policy->default_profile;

        return $this->unique($candidates);
    }

    public function channelCandidatesFor(FeedbackIntentData $intent): array
    {
        $requested = $this->requestedChannel($intent);
        $candidates = [];

        if ($requested !== null) {
            $candidates[] = $requested;

            foreach ($this->policy->channel_fallbacks[$requested] ?? [] as $fallback) {
                $candidates[] = $fallback === 'default' ? null : $fallback;
            }
        }

        $candidates[] = null;

        return $this->unique($candidates);
    }

    public function variablesFor(FeedbackIntentData $intent): array
    {
        return $this->featureProfileFor($intent)?->variables ?? [];
    }

    public function actionsFor(FeedbackIntentData $intent): array
    {
        return $this->featureProfileFor($intent)?->actions ?? [];
    }

    private function requestedProfile(FeedbackIntentData $intent): ?string
    {
        $profile = $intent->context?->meta['feature_profile'] ?? $intent->meta['feature_profile'] ?? null;

        return is_scalar($profile) && $profile !== '' ? (string) $profile : null;
    }

    private function requestedChannel(FeedbackIntentData $intent): ?string
    {
        $channel = $intent->channels[0]->key ?? null;

        return is_scalar($channel) && $channel !== '' ? (string) $channel : null;
    }

    /**
     * @param  array<int, string|null>  $values
     * @return array<int, string|null>
     */
    private function unique(array $values): array
    {
        $seen = [];
        $unique = [];

        foreach ($values as $value) {
            $key = $value ?? '__null__';

            if (array_key_exists($key, $seen)) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $value;
        }

        return $unique;
    }
}
