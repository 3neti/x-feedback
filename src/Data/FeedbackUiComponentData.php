<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackUiComponentData extends Data
{
    public const string NotificationBadge = 'notification_badge';

    public const string NotificationBell = 'notification_bell';

    public const string NotificationList = 'notification_list';

    public const string NotificationItem = 'notification_item';

    public const string DeliveryStatusBadge = 'delivery_status_badge';

    public const string DeliveryTimeline = 'delivery_timeline';

    public const string DeliveryAttemptTable = 'delivery_attempt_table';

    public const string ChannelIcon = 'channel_icon';

    public const string RetryDeliveryButton = 'retry_delivery_button';

    public function __construct(
        public string $key,
        public string $component,
        public array $props = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public static function componentKeys(): array
    {
        return [
            self::NotificationBadge,
            self::NotificationBell,
            self::NotificationList,
            self::NotificationItem,
            self::DeliveryStatusBadge,
            self::DeliveryTimeline,
            self::DeliveryAttemptTable,
            self::ChannelIcon,
            self::RetryDeliveryButton,
        ];
    }
}
