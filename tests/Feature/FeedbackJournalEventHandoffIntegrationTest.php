<?php

use LBHurtado\XFeedback\Contracts\FeedbackJournalEventMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\FeedbackJournalEventMapper;

it('models feedback journal event handoff facts without depending on x-journal', function () {
    $event = new FeedbackJournalEventData(
        event_name: FeedbackJournalEventData::EventSent,
        source: 'x-feedback',
        subject_type: 'feedback_delivery',
        subject_id: 'delivery-1',
        correlation_id: 'execution-1',
        causation_id: 'feedback-run-1',
        references: ['intent_key' => 'claim.succeeded.claimant'],
        payload: ['delivery_status' => FeedbackDeliveryData::StatusSent],
        meta: ['journal_ready' => true],
    );

    expect($event->event_name)->toBe('feedback.sent')
        ->and(FeedbackJournalEventData::eventNames())->toBe([
            FeedbackJournalEventData::EventCreated,
            FeedbackJournalEventData::EventSent,
            FeedbackJournalEventData::EventFailed,
            FeedbackJournalEventData::EventExpired,
        ])
        ->and($event->source)->toBe('x-feedback')
        ->and($event->subject_type)->toBe('feedback_delivery')
        ->and($event->correlation_id)->toBe('execution-1')
        ->and($event->meta['journal_ready'])->toBeTrue();
});

it('maps delivery record statuses into functional feedback journal event names', function (string $status, string $expectedEvent) {
    $event = app(FeedbackJournalEventMapperContract::class)->fromRecord(new FeedbackDeliveryRecordData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        status: $status,
        delivery_id: 'delivery-1',
        provider_message_id: 'provider-message-1',
        provider_status: strtoupper($status),
        correlation_id: 'execution-1',
        causation_id: 'feedback-run-1',
        meta: ['attempts' => 1],
    ));

    expect($event)->toBeInstanceOf(FeedbackJournalEventData::class)
        ->and($event->event_name)->toBe($expectedEvent)
        ->and($event->subject_id)->toBe('delivery-1')
        ->and($event->references)->toBe([
            'intent_key' => 'claim.succeeded.claimant',
            'channel' => 'sms',
            'delivery_id' => 'delivery-1',
            'provider_message_id' => 'provider-message-1',
            'provider_status' => strtoupper($status),
            'recipient_type' => 'claimant',
            'recipient_id' => 'user-1',
        ])
        ->and($event->payload['delivery_status'])->toBe($status)
        ->and($event->payload['recipient']['phone'])->toBe('+639171234567')
        ->and($event->meta['journal_ready'])->toBeTrue()
        ->and($event->meta['canonical_source'])->toBeFalse()
        ->and($event->meta['x_journal_dependency'])->toBeFalse();
})->with([
    'pending created' => ['pending', 'feedback.created'],
    'queued created' => ['queued', 'feedback.created'],
    'sent sent' => ['sent', 'feedback.sent'],
    'delivered sent' => ['delivered', 'feedback.sent'],
    'failed retryable failed' => ['failed_retryable', 'feedback.failed'],
    'failed final failed' => ['failed_final', 'feedback.failed'],
    'expired expired' => ['expired', 'feedback.expired'],
]);

it('maps provider receipts into journal event handoff facts with redacted provider payloads', function () {
    $event = app(FeedbackJournalEventMapperContract::class)->fromReceipt(new FeedbackProviderReceiptData(
        intent_key: 'claim.failed.operator',
        channel: 'webhook',
        recipient: new FeedbackRecipientData(type: 'operator', id: 'operator-1', email: 'operator@example.test'),
        status: FeedbackDeliveryData::StatusFailedFinal,
        provider_message_id: 'provider-message-2',
        provider_status: 'FAILED',
        provider_payload: [
            'status_code' => 500,
            'api_key' => 'secret-api-key',
            'nested' => ['token' => 'secret-token', 'safe' => 'visible'],
        ],
        correlation_id: 'execution-2',
        causation_id: 'feedback-run-2',
        occurred_at: '2026-07-02T12:00:00+08:00',
        meta: ['reason' => 'bounce'],
    ));

    expect($event->event_name)->toBe(FeedbackJournalEventData::EventFailed)
        ->and($event->subject_id)->toBe('claim.failed.operator:webhook:provider-message-2')
        ->and($event->payload['provider_payload']['api_key'])->toBe('[redacted]')
        ->and($event->payload['provider_payload']['nested']['token'])->toBe('[redacted]')
        ->and($event->payload['provider_payload']['nested']['safe'])->toBe('visible')
        ->and($event->payload['occurred_at'])->toBe('2026-07-02T12:00:00+08:00')
        ->and($event->meta['reason'])->toBe('bounce')
        ->and($event->meta['redacted'])->toBeTrue();
});

it('maps multiple delivery records into journal event handoffs without mutating records', function () {
    $records = [
        new FeedbackDeliveryRecordData(
            intent_key: 'claim.succeeded.claimant',
            channel: 'sms',
            recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
            status: FeedbackDeliveryData::StatusSent,
            delivery_id: 'delivery-1',
            correlation_id: 'execution-1',
        ),
        new FeedbackDeliveryRecordData(
            intent_key: 'claim.failed.operator',
            channel: 'email',
            recipient: new FeedbackRecipientData(type: 'operator', id: 'operator-1'),
            status: FeedbackDeliveryData::StatusFailedFinal,
            delivery_id: 'delivery-2',
            correlation_id: 'execution-2',
        ),
    ];

    $events = app(FeedbackJournalEventMapperContract::class)->fromRecords($records);

    expect($events)->toHaveCount(2)
        ->and($events[0]->event_name)->toBe(FeedbackJournalEventData::EventSent)
        ->and($events[1]->event_name)->toBe(FeedbackJournalEventData::EventFailed)
        ->and($records[0]->status)->toBe(FeedbackDeliveryData::StatusSent);
});

it('binds the feedback journal event mapper for package consumers', function () {
    expect(app(FeedbackJournalEventMapperContract::class))->toBeInstanceOf(FeedbackJournalEventMapper::class)
        ->and(app(FeedbackJournalEventMapperContract::class))->toBe(app(FeedbackJournalEventMapperContract::class));
});

it('keeps feedback journal event handoff independent from x-journal persistence events routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Events'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Listeners'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});
