<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackJournalEventData extends Data
{
    public const string EventCreated = 'feedback.created';

    public const string EventSent = 'feedback.sent';

    public const string EventFailed = 'feedback.failed';

    public const string EventExpired = 'feedback.expired';

    public function __construct(
        public string $event_name,
        public string $source = 'x-feedback',
        public string $subject_type = 'feedback_delivery',
        public string|int|null $subject_id = null,
        public ?string $actor_type = 'system',
        public string|int|null $actor_id = 'x-feedback',
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $references = [],
        public array $payload = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public static function eventNames(): array
    {
        return [
            self::EventCreated,
            self::EventSent,
            self::EventFailed,
            self::EventExpired,
        ];
    }
}
