<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackUiComponentPresenterContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;
use LBHurtado\XFeedback\Data\FeedbackUiComponentData;

final class FeedbackUiComponentPresenter implements FeedbackUiComponentPresenterContract
{
    public function notificationBadge(int $unreadCount): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::NotificationBadge,
            component: 'NotificationBadge',
            props: [
                'count' => max(0, $unreadCount),
                'visible' => $unreadCount > 0,
            ],
        );
    }

    public function notificationBell(int $unreadCount, bool $hasUnread): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::NotificationBell,
            component: 'NotificationBell',
            props: [
                'count' => max(0, $unreadCount),
                'has_unread' => $hasUnread,
            ],
        );
    }

    public function notificationList(array $notifications): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::NotificationList,
            component: 'NotificationList',
            props: [
                'items' => array_map(
                    fn (FeedbackInAppNotificationData $notification): array => $this->notificationItem($notification)->props,
                    $notifications,
                ),
            ],
        );
    }

    public function notificationItem(FeedbackInAppNotificationData $notification): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::NotificationItem,
            component: 'NotificationItem',
            props: [
                'delivery_id' => $notification->delivery_id,
                'intent_key' => $notification->intent_key,
                'state' => $notification->state,
                'title' => $this->stringMeta($notification->meta, 'title'),
                'body' => $this->stringMeta($notification->meta, 'body'),
                'recipient' => [
                    'type' => $notification->recipient->type,
                    'id' => $notification->recipient->id,
                ],
                'read_at' => $notification->read_at,
                'archived_at' => $notification->archived_at,
                'dismissed_at' => $notification->dismissed_at,
            ],
        );
    }

    public function deliveryStatusBadge(FeedbackDeliveryConsoleRecordData $record): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::DeliveryStatusBadge,
            component: 'DeliveryStatusBadge',
            props: [
                'delivery_id' => $record->delivery_id,
                'status' => $record->status,
                'label' => $this->statusLabel($record->status),
                'tone' => $this->statusTone($record->status),
            ],
        );
    }

    public function deliveryTimeline(FeedbackDeliveryConsoleHistoryData $history): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::DeliveryTimeline,
            component: 'DeliveryTimeline',
            props: [
                'items' => array_map(fn (FeedbackDeliveryConsoleRecordData $record): array => [
                    'delivery_id' => $record->delivery_id,
                    'status' => $record->status,
                    'label' => $this->statusLabel($record->status),
                    'tone' => $this->statusTone($record->status),
                    'channel' => $record->channel,
                    'last_attempted_at' => $record->last_attempted_at,
                ], $history->records),
                'filters' => $history->filters,
            ],
        );
    }

    public function deliveryAttemptTable(FeedbackDeliveryConsoleHistoryData $history): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::DeliveryAttemptTable,
            component: 'DeliveryAttemptTable',
            props: [
                'rows' => array_map(fn (FeedbackDeliveryConsoleRecordData $record): array => [
                    'delivery_id' => $record->delivery_id,
                    'intent_key' => $record->intent_key,
                    'channel' => $record->channel,
                    'status' => $record->status,
                    'attempt_count' => $record->attempt_count,
                    'provider_message_id' => $record->provider_message_id,
                    'provider_status' => $record->provider_status,
                    'last_attempted_at' => $record->last_attempted_at,
                ], $history->records),
                'total' => $history->total,
            ],
        );
    }

    public function channelIcon(string $channel): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::ChannelIcon,
            component: 'ChannelIcon',
            props: [
                'channel' => $channel,
                'icon' => $this->channelIconName($channel),
            ],
        );
    }

    public function retryDeliveryButton(FeedbackDeliveryConsoleRetryRequestData $request): FeedbackUiComponentData
    {
        return $this->component(
            key: FeedbackUiComponentData::RetryDeliveryButton,
            component: 'RetryDeliveryButton',
            props: [
                'delivery_id' => $request->delivery_id,
                'enabled' => $request->eligible,
                'reason' => $request->reason,
                'classification' => $request->decision->classification,
                'next_retry_at' => $request->decision->next_retry_at,
                'queues_retry' => false,
            ],
            meta: ['handoff_only' => true],
        );
    }

    private function component(string $key, string $component, array $props, array $meta = []): FeedbackUiComponentData
    {
        return new FeedbackUiComponentData(
            key: $key,
            component: $component,
            props: $props,
            meta: array_merge([
                'portable' => true,
                'cockpit_page' => false,
                'owns_workflow' => false,
                'owns_lifecycle_truth' => false,
            ], $meta),
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            FeedbackDeliveryData::StatusPending => 'Pending',
            FeedbackDeliveryData::StatusQueued => 'Queued',
            FeedbackDeliveryData::StatusSending => 'Sending',
            FeedbackDeliveryData::StatusSent => 'Sent',
            FeedbackDeliveryData::StatusDelivered => 'Delivered',
            FeedbackDeliveryData::StatusFailedRetryable,
            FeedbackDeliveryData::StatusFailedFinal => 'Failed',
            FeedbackDeliveryData::StatusRetryScheduled => 'Retry Scheduled',
            FeedbackDeliveryData::StatusExpired => 'Expired',
            FeedbackDeliveryData::StatusCancelled => 'Cancelled',
            default => 'Unknown',
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            FeedbackDeliveryData::StatusDelivered,
            FeedbackDeliveryData::StatusSent => 'success',
            FeedbackDeliveryData::StatusFailedRetryable,
            FeedbackDeliveryData::StatusFailedFinal,
            FeedbackDeliveryData::StatusExpired,
            FeedbackDeliveryData::StatusCancelled => 'danger',
            FeedbackDeliveryData::StatusQueued,
            FeedbackDeliveryData::StatusSending,
            FeedbackDeliveryData::StatusRetryScheduled => 'warning',
            default => 'neutral',
        };
    }

    private function channelIconName(string $channel): string
    {
        return match ($channel) {
            'email', 'mail' => 'mail',
            'sms' => 'message',
            'in_app' => 'bell',
            'webhook' => 'webhook',
            'log' => 'file-text',
            default => 'circle',
        };
    }

    private function stringMeta(array $meta, string $key): ?string
    {
        $value = $meta[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
