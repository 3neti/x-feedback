<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRetryDecisionData extends Data
{
    public const string ClassificationRetryable = 'retryable';

    public const string ClassificationFinal = 'final';

    public const string ClassificationExpired = 'expired';

    public const string ClassificationExhausted = 'exhausted';

    public const string ClassificationPending = 'pending';

    public function __construct(
        public string $classification,
        public bool $should_retry = false,
        public bool $should_expire = false,
        public int $attempts = 0,
        public ?string $next_retry_at = null,
        public ?string $reason = null,
        public array $meta = [],
    ) {}
}
