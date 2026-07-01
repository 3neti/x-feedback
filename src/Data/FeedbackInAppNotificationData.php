<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackInAppNotificationData extends Data
{
    public const string StateUnread = 'unread';

    public const string StateRead = 'read';

    public const string StateArchived = 'archived';

    public const string StateDismissed = 'dismissed';

    public function __construct(
        public string $delivery_id,
        public string $intent_key,
        public FeedbackRecipientData $recipient,
        public string $state = self::StateUnread,
        public ?string $read_at = null,
        public ?string $archived_at = null,
        public ?string $dismissed_at = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public static function states(): array
    {
        return [
            self::StateUnread,
            self::StateRead,
            self::StateArchived,
            self::StateDismissed,
        ];
    }
}
