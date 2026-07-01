<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;

interface FeedbackInAppNotificationStateManagerContract
{
    public function markRead(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData;

    public function markUnread(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData;

    public function archive(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData;

    public function dismiss(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData;

    /**
     * @return array<int, FeedbackInAppNotificationData>
     */
    public function bulkMarkRead(string $recipientType, ?string $recipientId = null, ?string $actorId = null, ?string $occurredAt = null): array;

    /**
     * @return array<int, FeedbackInAppNotificationData>
     */
    public function forRecipient(string $recipientType, ?string $recipientId = null, bool $includeHidden = false): array;
}
