<?php

namespace LBHurtado\XFeedback\Services;

use DateTimeImmutable;
use DateTimeZone;
use LBHurtado\XFeedback\Contracts\FeedbackSuppressionEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackNotificationPreferenceData;
use LBHurtado\XFeedback\Data\FeedbackQuietHoursData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionDecisionData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionPolicyData;

final class FeedbackSuppressionEvaluator implements FeedbackSuppressionEvaluatorContract
{
    public function evaluate(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData|string $channel,
        ?FeedbackSuppressionPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackSuppressionDecisionData {
        $policy ??= new FeedbackSuppressionPolicyData;
        $channelKey = $channel instanceof FeedbackChannelData ? $channel->key : $channel;

        if (in_array($channelKey, $policy->disabled_channels, true)) {
            return $this->suppressed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonDisabledChannel);
        }

        if (in_array($channelKey, $policy->opt_out_channels, true)) {
            return $this->suppressed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonOptedOut);
        }

        if ($this->hasDisabledPreference($intent, $recipient, $channelKey, $policy)) {
            return $this->suppressed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonPreferenceDisabled);
        }

        if ($this->isExpired($intent, $now)) {
            return $this->suppressed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonExpired);
        }

        if ($this->isStale($intent, $policy, $now)) {
            return $this->suppressed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonStale);
        }

        if (! $this->isRequired($channelKey, $policy) && $quietHours = $this->matchingQuietHours($recipient, $channelKey, $policy, $now)) {
            return $this->suppressed(
                $intent,
                $recipient,
                $channelKey,
                FeedbackSuppressionDecisionData::ReasonQuietHours,
                [
                    'quiet_hours_start_time' => $quietHours->start_time,
                    'quiet_hours_end_time' => $quietHours->end_time,
                    'quiet_hours_timezone' => $quietHours->timezone,
                ],
            );
        }

        if ($this->isRequired($channelKey, $policy)) {
            return $this->allowed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonRequired, ['required' => true]);
        }

        return $this->allowed($intent, $recipient, $channelKey, FeedbackSuppressionDecisionData::ReasonAllowed);
    }

    private function hasDisabledPreference(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        string $channel,
        FeedbackSuppressionPolicyData $policy,
    ): bool {
        foreach ($policy->preferences as $preference) {
            if ($preference instanceof FeedbackNotificationPreferenceData
                && ! $preference->enabled
                && $this->matchesPreference($preference, $intent, $recipient, $channel)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPreference(
        FeedbackNotificationPreferenceData $preference,
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        string $channel,
    ): bool {
        if ($preference->event_key !== $intent->key || $preference->channel !== $channel) {
            return false;
        }

        if ($preference->recipient_type !== null && $preference->recipient_type !== $recipient->type) {
            return false;
        }

        if ($preference->recipient_id !== null && (string) $preference->recipient_id !== (string) $recipient->id) {
            return false;
        }

        return true;
    }

    private function isExpired(FeedbackIntentData $intent, ?string $now): bool
    {
        if ($intent->expires_at === null || $intent->expires_at === '') {
            return false;
        }

        return $this->timestamp($intent->expires_at) <= $this->timestamp($now);
    }

    private function isStale(FeedbackIntentData $intent, FeedbackSuppressionPolicyData $policy, ?string $now): bool
    {
        if ($policy->stale_after_seconds === null) {
            return false;
        }

        $createdAt = $intent->meta['created_at'] ?? null;

        if (! is_string($createdAt) || $createdAt === '') {
            return false;
        }

        return $this->timestamp($now) - $this->timestamp($createdAt) > $policy->stale_after_seconds;
    }

    private function matchingQuietHours(
        FeedbackRecipientData $recipient,
        string $channel,
        FeedbackSuppressionPolicyData $policy,
        ?string $now,
    ): ?FeedbackQuietHoursData {
        foreach ($policy->quiet_hours as $quietHours) {
            if ($quietHours instanceof FeedbackQuietHoursData
                && $this->quietHoursApplyTo($quietHours, $recipient, $channel)
                && $this->isWithinQuietHours($quietHours, $now)) {
                return $quietHours;
            }
        }

        return null;
    }

    private function quietHoursApplyTo(FeedbackQuietHoursData $quietHours, FeedbackRecipientData $recipient, string $channel): bool
    {
        if ($quietHours->channels !== [] && ! in_array($channel, $quietHours->channels, true)) {
            return false;
        }

        if ($quietHours->recipient_types !== [] && ! in_array($recipient->type, $quietHours->recipient_types, true)) {
            return false;
        }

        return true;
    }

    private function isWithinQuietHours(FeedbackQuietHoursData $quietHours, ?string $now): bool
    {
        $current = new DateTimeImmutable($now ?? 'now');
        $current = $current->setTimezone(new DateTimeZone($quietHours->timezone));

        $currentMinutes = $this->minutes($current->format('H:i'));
        $startMinutes = $this->minutes($quietHours->start_time);
        $endMinutes = $this->minutes($quietHours->end_time);

        if ($startMinutes === $endMinutes) {
            return true;
        }

        if ($startMinutes < $endMinutes) {
            return $currentMinutes >= $startMinutes && $currentMinutes < $endMinutes;
        }

        return $currentMinutes >= $startMinutes || $currentMinutes < $endMinutes;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time, 3), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function isRequired(string $channel, FeedbackSuppressionPolicyData $policy): bool
    {
        return in_array($channel, $policy->required_channels, true);
    }

    private function timestamp(?string $value): int
    {
        return (new DateTimeImmutable($value ?? 'now'))->getTimestamp();
    }

    private function suppressed(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        string $channel,
        string $reason,
        array $meta = [],
    ): FeedbackSuppressionDecisionData {
        return new FeedbackSuppressionDecisionData(
            allowed: false,
            suppressed: true,
            reason: $reason,
            intent_key: $intent->key,
            channel: $channel,
            recipient_id: $recipient->id,
            recipient_type: $recipient->type,
            meta: $meta,
        );
    }

    private function allowed(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        string $channel,
        string $reason,
        array $meta = [],
    ): FeedbackSuppressionDecisionData {
        return new FeedbackSuppressionDecisionData(
            allowed: true,
            suppressed: false,
            reason: $reason,
            intent_key: $intent->key,
            channel: $channel,
            recipient_id: $recipient->id,
            recipient_type: $recipient->type,
            meta: $meta,
        );
    }
}
