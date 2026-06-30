<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackChannelHealthData extends Data
{
    public const StatusAvailable = 'available';

    public const StatusDegraded = 'degraded';

    public const StatusUnavailable = 'unavailable';

    public function __construct(
        public string $channel,
        public bool $healthy,
        public string $status = self::StatusAvailable,
        public array $details = [],
    ) {}
}
