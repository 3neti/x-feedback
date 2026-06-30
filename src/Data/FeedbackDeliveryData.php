<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryData extends Data
{
    public const StatusPending = 'pending';

    public const StatusQueued = 'queued';

    public const StatusSending = 'sending';

    public const StatusSent = 'sent';

    public const StatusDelivered = 'delivered';

    public const StatusFailedRetryable = 'failed_retryable';

    public const StatusRetryScheduled = 'retry_scheduled';

    public const StatusFailedFinal = 'failed_final';

    public const StatusExpired = 'expired';

    public const StatusCancelled = 'cancelled';

    public function __construct(
        public string $intent_key,
        public string $channel,
        public FeedbackRecipientData $recipient,
        public string $status = self::StatusPending,
        public ?string $provider_message_id = null,
        public array $result = [],
        public array $error = [],
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::StatusPending,
            self::StatusQueued,
            self::StatusSending,
            self::StatusSent,
            self::StatusDelivered,
            self::StatusFailedRetryable,
            self::StatusRetryScheduled,
            self::StatusFailedFinal,
            self::StatusExpired,
            self::StatusCancelled,
        ];
    }
}
