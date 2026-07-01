<?php

use Illuminate\Support\Facades\Schema;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackInAppNotificationStateManagerContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Models\FeedbackDeliveryRecord;
use LBHurtado\XFeedback\Services\FeedbackInAppNotificationStateManager;

it('loads in-app notification state columns onto durable delivery records', function () {
    expect(Schema::hasColumns('feedback_delivery_records', [
        'in_app_state',
        'read_at',
        'archived_at',
        'dismissed_at',
    ]))->toBeTrue();
});

it('models in-app notification states without becoming workflow truth', function () {
    $notification = new FeedbackInAppNotificationData(
        delivery_id: 'delivery-1',
        intent_key: 'claim.succeeded.claimant',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
        state: FeedbackInAppNotificationData::StateUnread,
        meta: ['source' => 'delivery-record'],
    );

    expect($notification->state)->toBe(FeedbackInAppNotificationData::StateUnread)
        ->and(FeedbackInAppNotificationData::states())->toBe([
            FeedbackInAppNotificationData::StateUnread,
            FeedbackInAppNotificationData::StateRead,
            FeedbackInAppNotificationData::StateArchived,
            FeedbackInAppNotificationData::StateDismissed,
        ])
        ->and($notification->meta)->not->toHaveKey('claim_status')
        ->and($notification->meta)->not->toHaveKey('workflow_action');
});

it('defaults in-app delivery records to unread notification state', function () {
    $record = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt())[0];

    expect($record->channel)->toBe('in_app')
        ->and($record->in_app_state)->toBe(FeedbackInAppNotificationData::StateUnread)
        ->and($record->read_at)->toBeNull()
        ->and($record->archived_at)->toBeNull()
        ->and($record->dismissed_at)->toBeNull();
});

it('marks in-app notifications read and unread without changing delivery status', function () {
    $record = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(status: 'delivered'))[0];
    $manager = app(FeedbackInAppNotificationStateManagerContract::class);

    $read = $manager->markRead($record->delivery_id, actorId: 'operator-1', occurredAt: '2026-07-01T01:00:00+00:00');
    $unread = $manager->markUnread($record->delivery_id, actorId: 'operator-1');

    $model = FeedbackDeliveryRecord::query()->where('delivery_id', $record->delivery_id)->firstOrFail();

    expect($read->state)->toBe(FeedbackInAppNotificationData::StateRead)
        ->and($read->read_at)->not->toBeNull()
        ->and($unread->state)->toBe(FeedbackInAppNotificationData::StateUnread)
        ->and($unread->read_at)->toBeNull()
        ->and($model->status)->toBe('delivered')
        ->and($model->meta['in_app_state_actor_id'])->toBe('operator-1');
});

it('archives and dismisses in-app notifications without mutating delivery truth', function () {
    $first = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-1'))[0];
    $second = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-2'))[0];
    $manager = app(FeedbackInAppNotificationStateManagerContract::class);

    $archived = $manager->archive($first->delivery_id, actorId: 'operator-1', occurredAt: '2026-07-01T02:00:00+00:00');
    $dismissed = $manager->dismiss($second->delivery_id, actorId: 'operator-1', occurredAt: '2026-07-01T03:00:00+00:00');

    expect($archived->state)->toBe(FeedbackInAppNotificationData::StateArchived)
        ->and($archived->archived_at)->not->toBeNull()
        ->and($dismissed->state)->toBe(FeedbackInAppNotificationData::StateDismissed)
        ->and($dismissed->dismissed_at)->not->toBeNull()
        ->and(FeedbackDeliveryRecord::query()->where('delivery_id', $first->delivery_id)->value('status'))->toBe('sent')
        ->and(FeedbackDeliveryRecord::query()->where('delivery_id', $second->delivery_id)->value('status'))->toBe('sent');
});

it('bulk marks unread in-app notifications as read for a recipient', function () {
    app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-1'));
    app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-2'));
    app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-3', recipientId: 'user-2'));

    $updated = app(FeedbackInAppNotificationStateManagerContract::class)->bulkMarkRead(
        recipientType: 'claimant',
        recipientId: 'user-1',
        actorId: 'operator-1',
        occurredAt: '2026-07-01T04:00:00+00:00',
    );

    expect($updated)->toHaveCount(2)
        ->and($updated[0]->state)->toBe(FeedbackInAppNotificationData::StateRead)
        ->and($updated[1]->state)->toBe(FeedbackInAppNotificationData::StateRead)
        ->and(FeedbackDeliveryRecord::query()->where('recipient_id', 'user-2')->value('in_app_state'))->toBe(FeedbackInAppNotificationData::StateUnread);
});

it('lists visible in-app notifications for a recipient without returning archived or dismissed records by default', function () {
    $first = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-1'))[0];
    $second = app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-2'))[0];
    app(FeedbackDeliveryAttemptRecorderContract::class)->record(feedbackInAppAttempt(providerMessageId: 'in-app-3'));
    $manager = app(FeedbackInAppNotificationStateManagerContract::class);

    $manager->archive($first->delivery_id);
    $manager->dismiss($second->delivery_id);

    $visible = $manager->forRecipient('claimant', 'user-1');
    $includingHidden = $manager->forRecipient('claimant', 'user-1', includeHidden: true);

    expect($visible)->toHaveCount(1)
        ->and($visible[0]->state)->toBe(FeedbackInAppNotificationData::StateUnread)
        ->and($includingHidden)->toHaveCount(3);
});

it('binds the in-app notification state manager for package consumers', function () {
    expect(app(FeedbackInAppNotificationStateManagerContract::class))->toBeInstanceOf(FeedbackInAppNotificationStateManager::class)
        ->and(app(FeedbackInAppNotificationStateManagerContract::class))->toBe(app(FeedbackInAppNotificationStateManagerContract::class));
});

it('keeps in-app notification baseline independent from cockpit pages frontend workflow mutation and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackInAppAttempt(
    string $status = 'sent',
    string $providerMessageId = 'in-app-provider-1',
    string $recipientId = 'user-1',
): FeedbackDeliveryAttemptData {
    return new FeedbackDeliveryAttemptData(
        intent_key: 'claim.succeeded.claimant',
        receipts: [
            new FeedbackProviderReceiptData(
                intent_key: 'claim.succeeded.claimant',
                channel: 'in_app',
                recipient: new FeedbackRecipientData(type: 'claimant', id: $recipientId),
                status: $status,
                provider_message_id: $providerMessageId,
                provider_status: 'ACCEPTED',
                provider_payload: ['provider' => 'in_app'],
                correlation_id: 'execution-1',
                causation_id: 'feedback-run-1',
                occurred_at: '2026-07-01T00:00:00+00:00',
                meta: ['max_attempts' => 1],
            ),
        ],
    );
}
