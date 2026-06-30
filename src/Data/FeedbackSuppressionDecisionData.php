<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackSuppressionDecisionData extends Data
{
    public const string ReasonAllowed = 'allowed';

    public const string ReasonRequired = 'required_channel';

    public const string ReasonDisabledChannel = 'disabled_channel';

    public const string ReasonOptedOut = 'opted_out';

    public const string ReasonPreferenceDisabled = 'preference_disabled';

    public const string ReasonQuietHours = 'quiet_hours';

    public const string ReasonExpired = 'expired';

    public const string ReasonStale = 'stale';

    public function __construct(
        public bool $allowed,
        public bool $suppressed,
        public string $reason,
        public string $intent_key,
        public string $channel,
        public string|int|null $recipient_id = null,
        public ?string $recipient_type = null,
        public array $meta = [],
    ) {}
}
