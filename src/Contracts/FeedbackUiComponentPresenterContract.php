<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;
use LBHurtado\XFeedback\Data\FeedbackUiComponentData;

interface FeedbackUiComponentPresenterContract
{
    public function notificationBadge(int $unreadCount): FeedbackUiComponentData;

    public function notificationBell(int $unreadCount, bool $hasUnread): FeedbackUiComponentData;

    /**
     * @param  array<int, FeedbackInAppNotificationData>  $notifications
     */
    public function notificationList(array $notifications): FeedbackUiComponentData;

    public function notificationItem(FeedbackInAppNotificationData $notification): FeedbackUiComponentData;

    public function deliveryStatusBadge(FeedbackDeliveryConsoleRecordData $record): FeedbackUiComponentData;

    public function deliveryTimeline(FeedbackDeliveryConsoleHistoryData $history): FeedbackUiComponentData;

    public function deliveryAttemptTable(FeedbackDeliveryConsoleHistoryData $history): FeedbackUiComponentData;

    public function channelIcon(string $channel): FeedbackUiComponentData;

    public function retryDeliveryButton(FeedbackDeliveryConsoleRetryRequestData $request): FeedbackUiComponentData;
}
