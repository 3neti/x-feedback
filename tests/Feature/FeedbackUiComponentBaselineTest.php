<?php

use LBHurtado\XFeedback\Contracts\FeedbackUiComponentPresenterContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackInAppNotificationData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRetryDecisionData;
use LBHurtado\XFeedback\Data\FeedbackUiComponentData;
use LBHurtado\XFeedback\Services\FeedbackUiComponentPresenter;

it('models reusable feedback ui components as portable view models', function () {
    $component = new FeedbackUiComponentData(
        key: FeedbackUiComponentData::NotificationBadge,
        component: 'NotificationBadge',
        props: ['count' => 3],
        meta: ['cockpit_page' => false],
    );

    expect($component->key)->toBe('notification_badge')
        ->and($component->component)->toBe('NotificationBadge')
        ->and($component->props)->toBe(['count' => 3])
        ->and(FeedbackUiComponentData::componentKeys())->toBe([
            FeedbackUiComponentData::NotificationBadge,
            FeedbackUiComponentData::NotificationBell,
            FeedbackUiComponentData::NotificationList,
            FeedbackUiComponentData::NotificationItem,
            FeedbackUiComponentData::DeliveryStatusBadge,
            FeedbackUiComponentData::DeliveryTimeline,
            FeedbackUiComponentData::DeliveryAttemptTable,
            FeedbackUiComponentData::ChannelIcon,
            FeedbackUiComponentData::RetryDeliveryButton,
        ]);
});

it('builds notification badge bell list and item view models without owning notification pages', function () {
    $presenter = app(FeedbackUiComponentPresenterContract::class);
    $notification = new FeedbackInAppNotificationData(
        delivery_id: 'delivery-1',
        intent_key: 'claim.succeeded.claimant',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'secret@example.test', phone: '+639171234567'),
        state: FeedbackInAppNotificationData::StateUnread,
        meta: ['title' => 'Claim approved', 'body' => 'Your claim was approved.'],
    );

    $badge = $presenter->notificationBadge(unreadCount: 3);
    $bell = $presenter->notificationBell(unreadCount: 3, hasUnread: true);
    $item = $presenter->notificationItem($notification);
    $list = $presenter->notificationList([$notification]);

    expect($badge->key)->toBe(FeedbackUiComponentData::NotificationBadge)
        ->and($badge->props)->toBe(['count' => 3, 'visible' => true])
        ->and($bell->props)->toBe(['count' => 3, 'has_unread' => true])
        ->and($item->key)->toBe(FeedbackUiComponentData::NotificationItem)
        ->and($item->props['recipient'])->toBe(['type' => 'claimant', 'id' => 'user-1'])
        ->and($item->props)->not->toHaveKey('email')
        ->and($item->props)->not->toHaveKey('phone')
        ->and($list->key)->toBe(FeedbackUiComponentData::NotificationList)
        ->and($list->props['items'])->toHaveCount(1)
        ->and($list->meta['cockpit_page'])->toBeFalse();
});

it('builds delivery status badge timeline and attempt table view models from console records', function () {
    $presenter = app(FeedbackUiComponentPresenterContract::class);
    $sent = feedbackUiDeliveryRecord(status: FeedbackDeliveryData::StatusSent, deliveryId: 'delivery-1', channel: 'sms');
    $failed = feedbackUiDeliveryRecord(status: FeedbackDeliveryData::StatusFailedFinal, deliveryId: 'delivery-2', channel: 'webhook');
    $history = new FeedbackDeliveryConsoleHistoryData(total: 2, records: [$sent, $failed], filters: ['correlation_id' => 'execution-1']);

    $badge = $presenter->deliveryStatusBadge($failed);
    $timeline = $presenter->deliveryTimeline($history);
    $table = $presenter->deliveryAttemptTable($history);

    expect($badge->key)->toBe(FeedbackUiComponentData::DeliveryStatusBadge)
        ->and($badge->props)->toBe([
            'delivery_id' => 'delivery-2',
            'status' => FeedbackDeliveryData::StatusFailedFinal,
            'label' => 'Failed',
            'tone' => 'danger',
        ])
        ->and($timeline->key)->toBe(FeedbackUiComponentData::DeliveryTimeline)
        ->and($timeline->props['items'])->toHaveCount(2)
        ->and($timeline->props['items'][0]['delivery_id'])->toBe('delivery-1')
        ->and($table->key)->toBe(FeedbackUiComponentData::DeliveryAttemptTable)
        ->and($table->props['rows'])->toHaveCount(2)
        ->and($table->props['rows'][1])->not->toHaveKey('provider_response');
});

it('builds channel icon and retry delivery button view models without executing retries', function () {
    $presenter = app(FeedbackUiComponentPresenterContract::class);
    $decision = new FeedbackRetryDecisionData(
        classification: FeedbackRetryDecisionData::ClassificationRetryable,
        should_retry: true,
        attempts: 1,
        next_retry_at: '2026-07-02T01:05:00+08:00',
        reason: 'retryable_status',
    );
    $request = new FeedbackDeliveryConsoleRetryRequestData(
        delivery_id: 'delivery-1',
        requested_by: 'operator-1',
        eligible: true,
        decision: $decision,
        reason: 'manual retry requested',
        meta: ['queues_retry' => false],
    );

    $icon = $presenter->channelIcon('webhook');
    $button = $presenter->retryDeliveryButton($request);

    expect($icon->key)->toBe(FeedbackUiComponentData::ChannelIcon)
        ->and($icon->props)->toBe(['channel' => 'webhook', 'icon' => 'webhook'])
        ->and($button->key)->toBe(FeedbackUiComponentData::RetryDeliveryButton)
        ->and($button->props['delivery_id'])->toBe('delivery-1')
        ->and($button->props['enabled'])->toBeTrue()
        ->and($button->props['queues_retry'])->toBeFalse()
        ->and($button->props['next_retry_at'])->toBe('2026-07-02T01:05:00+08:00')
        ->and($button->meta['handoff_only'])->toBeTrue();
});

it('binds the ui component presenter for package consumers', function () {
    expect(app(FeedbackUiComponentPresenterContract::class))->toBeInstanceOf(FeedbackUiComponentPresenter::class)
        ->and(app(FeedbackUiComponentPresenterContract::class))->toBe(app(FeedbackUiComponentPresenterContract::class));
});

it('keeps ui component baseline independent from cockpit pages frontend assets routes and workflow ownership', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/js'))->toBeFalse()
        ->and(is_dir($packageRoot.'/resources/vue'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackUiDeliveryRecord(string $status, string $deliveryId, string $channel): FeedbackDeliveryConsoleRecordData
{
    return new FeedbackDeliveryConsoleRecordData(
        delivery_id: $deliveryId,
        intent_key: 'claim.succeeded.claimant',
        channel: $channel,
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'secret@example.test', phone: '+639171234567'),
        status: $status,
        attempt_count: 1,
        provider_message_id: $deliveryId.'-provider',
        provider_status: strtoupper($status),
        correlation_id: 'execution-1',
        last_attempted_at: '2026-07-02T01:00:00+08:00',
    );
}
