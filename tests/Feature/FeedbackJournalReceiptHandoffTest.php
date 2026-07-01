<?php

use LBHurtado\XFeedback\Contracts\FeedbackJournalReceiptMapperContract;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalReceiptData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\FeedbackJournalReceiptMapper;

it('models journal-ready feedback receipt handoff payloads without depending on x-journal', function () {
    $handoff = new FeedbackJournalReceiptData(
        event_type: 'feedback.delivery.sent',
        source: 'x-feedback',
        subject_type: 'feedback_delivery',
        subject_id: 'claim.succeeded.claimant:null:provider-message-1',
        actor_type: 'system',
        actor_id: 'x-feedback',
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        references: ['provider_message_id' => 'provider-message-1'],
        payload: ['status' => 'sent'],
        meta: ['journal_ready' => true],
    );

    expect($handoff->event_type)->toBe('feedback.delivery.sent')
        ->and($handoff->source)->toBe('x-feedback')
        ->and($handoff->subject_type)->toBe('feedback_delivery')
        ->and($handoff->subject_id)->toBe('claim.succeeded.claimant:null:provider-message-1')
        ->and($handoff->actor_type)->toBe('system')
        ->and($handoff->actor_id)->toBe('x-feedback')
        ->and($handoff->correlation_id)->toBe('execution-1')
        ->and($handoff->causation_id)->toBe('journal-1')
        ->and($handoff->references)->toBe(['provider_message_id' => 'provider-message-1'])
        ->and($handoff->payload)->toBe(['status' => 'sent'])
        ->and($handoff->meta)->toBe(['journal_ready' => true]);
});

it('maps delivery records into journal-ready feedback receipt facts', function () {
    $record = new FeedbackDeliveryRecordData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'sms',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
        status: 'sent',
        provider_message_id: 'provider-message-1',
        provider_status: 'ACCEPTED',
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        meta: ['non_canonical' => true],
    );

    $handoff = app(FeedbackJournalReceiptMapperContract::class)->fromRecord($record);

    expect($handoff)->toBeInstanceOf(FeedbackJournalReceiptData::class)
        ->and($handoff->event_type)->toBe('feedback.delivery.sent')
        ->and($handoff->source)->toBe('x-feedback')
        ->and($handoff->subject_type)->toBe('feedback_delivery')
        ->and($handoff->subject_id)->toBe('claim.succeeded.claimant:sms:provider-message-1')
        ->and($handoff->correlation_id)->toBe('execution-1')
        ->and($handoff->causation_id)->toBe('journal-1')
        ->and($handoff->references)->toBe([
            'intent_key' => 'claim.succeeded.claimant',
            'channel' => 'sms',
            'provider_message_id' => 'provider-message-1',
            'provider_status' => 'ACCEPTED',
            'recipient_type' => 'claimant',
            'recipient_id' => 'user-1',
        ])
        ->and($handoff->payload['status'])->toBe('sent')
        ->and($handoff->payload['recipient']['phone'])->toBe('+639171234567')
        ->and($handoff->meta['non_canonical'])->toBeTrue();
});

it('maps provider receipts into journal-ready feedback receipt facts', function () {
    $receipt = new FeedbackProviderReceiptData(
        intent_key: 'claim.failed.operator',
        channel: 'email',
        recipient: new FeedbackRecipientData(type: 'operator', id: 'operator-1', email: 'operator@example.test'),
        status: 'failed_final',
        provider_message_id: 'provider-message-2',
        provider_status: 'BOUNCED',
        provider_payload: ['raw' => 'payload'],
        correlation_id: 'execution-2',
        causation_id: 'journal-2',
        occurred_at: '2026-06-30T12:00:00+08:00',
        meta: ['reason' => 'bounce'],
    );

    $handoff = app(FeedbackJournalReceiptMapperContract::class)->fromReceipt($receipt);

    expect($handoff->event_type)->toBe('feedback.delivery.failed_final')
        ->and($handoff->subject_id)->toBe('claim.failed.operator:email:provider-message-2')
        ->and($handoff->payload['provider_payload'])->toBe(['raw' => 'payload'])
        ->and($handoff->payload['occurred_at'])->toBe('2026-06-30T12:00:00+08:00')
        ->and($handoff->references['recipient_id'])->toBe('operator-1')
        ->and($handoff->meta['reason'])->toBe('bounce');
});

it('maps multiple delivery records into journal-ready handoff payloads without mutating records', function () {
    $records = [
        new FeedbackDeliveryRecordData(
            intent_key: 'claim.succeeded.claimant',
            channel: 'null',
            recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
            status: 'sent',
            correlation_id: 'execution-1',
        ),
        new FeedbackDeliveryRecordData(
            intent_key: 'claim.succeeded.issuer',
            channel: 'null',
            recipient: new FeedbackRecipientData(type: 'issuer', id: 'issuer-1'),
            status: 'delivered',
            correlation_id: 'execution-1',
        ),
    ];

    $handoffs = app(FeedbackJournalReceiptMapperContract::class)->fromRecords($records);

    expect($handoffs)->toHaveCount(2)
        ->and($handoffs[0]->event_type)->toBe('feedback.delivery.sent')
        ->and($handoffs[1]->event_type)->toBe('feedback.delivery.delivered')
        ->and($records[0]->status)->toBe('sent');
});

it('binds the journal receipt mapper for package consumers', function () {
    expect(app(FeedbackJournalReceiptMapperContract::class))->toBeInstanceOf(FeedbackJournalReceiptMapper::class)
        ->and(app(FeedbackJournalReceiptMapperContract::class))->toBe(app(FeedbackJournalReceiptMapperContract::class));
});

it('keeps journal receipt handoff independent from x-journal persistence routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});
