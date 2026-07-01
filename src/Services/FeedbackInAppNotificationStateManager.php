<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use LBHurtado\XFeedback\Contracts\FeedbackInAppNotificationStateManagerContract;
use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;

final class FeedbackInAppNotificationStateManager implements FeedbackInAppNotificationStateManagerContract
{
    public function markRead(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData
    {
        return $this->transition($deliveryId, FeedbackInAppNotificationData::StateRead, $actorId, $occurredAt);
    }

    public function markUnread(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData
    {
        return $this->transition($deliveryId, FeedbackInAppNotificationData::StateUnread, $actorId, $occurredAt);
    }

    public function archive(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData
    {
        return $this->transition($deliveryId, FeedbackInAppNotificationData::StateArchived, $actorId, $occurredAt);
    }

    public function dismiss(string $deliveryId, ?string $actorId = null, ?string $occurredAt = null): FeedbackInAppNotificationData
    {
        return $this->transition($deliveryId, FeedbackInAppNotificationData::StateDismissed, $actorId, $occurredAt);
    }

    public function bulkMarkRead(string $recipientType, ?string $recipientId = null, ?string $actorId = null, ?string $occurredAt = null): array
    {
        return $this->recipientQuery($recipientType, $recipientId)
            ->where('in_app_state', FeedbackInAppNotificationData::StateUnread)
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackInAppNotificationData => $this->markRead($record->delivery_id, $actorId, $occurredAt))
            ->all();
    }

    public function forRecipient(string $recipientType, ?string $recipientId = null, bool $includeHidden = false): array
    {
        $query = $this->recipientQuery($recipientType, $recipientId);

        if (! $includeHidden) {
            $query->whereNotIn('in_app_state', [
                FeedbackInAppNotificationData::StateArchived,
                FeedbackInAppNotificationData::StateDismissed,
            ]);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FeedbackDeliveryRecord $record): FeedbackInAppNotificationData => $this->toData($record))
            ->all();
    }

    private function transition(string $deliveryId, string $state, ?string $actorId, ?string $occurredAt): FeedbackInAppNotificationData
    {
        $record = FeedbackDeliveryRecord::query()
            ->where('delivery_id', $deliveryId)
            ->where('channel', 'in_app')
            ->firstOrFail();
        $timestamp = $this->timestamp($occurredAt);
        $meta = (array) $record->meta;
        $meta['in_app_state_actor_id'] = $actorId;
        $meta['in_app_state_changed_at'] = $timestamp;

        $record->fill([
            'in_app_state' => $state,
            'read_at' => $state === FeedbackInAppNotificationData::StateRead ? $timestamp : null,
            'archived_at' => $state === FeedbackInAppNotificationData::StateArchived ? $timestamp : $record->archived_at,
            'dismissed_at' => $state === FeedbackInAppNotificationData::StateDismissed ? $timestamp : $record->dismissed_at,
            'meta' => $meta,
        ]);
        $record->save();

        return $this->toData($record);
    }

    private function recipientQuery(string $recipientType, ?string $recipientId): Builder
    {
        return FeedbackDeliveryRecord::query()
            ->where('channel', 'in_app')
            ->where('recipient_type', $recipientType)
            ->when($recipientId !== null, fn (Builder $query): Builder => $query->where('recipient_id', $recipientId));
    }

    private function timestamp(?string $occurredAt): string
    {
        return $occurredAt ?? Carbon::now()->toISOString();
    }

    private function toData(FeedbackDeliveryRecord $record): FeedbackInAppNotificationData
    {
        return new FeedbackInAppNotificationData(
            delivery_id: $record->delivery_id,
            intent_key: $record->intent_key,
            recipient: new FeedbackRecipientData(...(array) $record->recipient),
            state: $record->in_app_state ?? FeedbackInAppNotificationData::StateUnread,
            read_at: $record->read_at?->toISOString(),
            archived_at: $record->archived_at?->toISOString(),
            dismissed_at: $record->dismissed_at?->toISOString(),
            meta: (array) $record->meta,
        );
    }
}
