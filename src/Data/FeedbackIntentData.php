<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackIntentData extends Data
{
    /**
     * @param  array<int, FeedbackRecipientData>  $recipients
     * @param  array<int, FeedbackChannelData>  $channels
     */
    public function __construct(
        public string $key,
        public FeedbackMessageData $message,
        public array $recipients = [],
        public array $channels = [],
        public ?FeedbackContextData $context = null,
        public ?string $priority = 'normal',
        public ?string $expires_at = null,
        public array $meta = [],
    ) {}

    public static function forEvent(
        string $key,
        string $eventType,
        FeedbackMessageData $message,
        array $recipients = [],
        array $channels = [],
        ?string $source = null,
        ?string $correlationId = null,
        ?string $causationId = null,
        ?string $subjectType = null,
        string|int|null $subjectId = null,
        array $meta = [],
    ): self {
        return new self(
            key: $key,
            message: $message,
            recipients: $recipients,
            channels: $channels,
            context: new FeedbackContextData(
                event_type: $eventType,
                source: $source,
                correlation_id: $correlationId,
                causation_id: $causationId,
                subject_id: $subjectId,
                subject_type: $subjectType,
            ),
            meta: $meta,
        );
    }
}
