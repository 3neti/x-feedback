<?php

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRecorderContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRuntimeContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\InMemoryFeedbackDeliveryAttemptRecorder;

it('models delivery records as non-canonical delivery facts', function () {
    $record = new FeedbackDeliveryRecordData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'null',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
        status: 'sent',
        provider_message_id: 'provider-message-1',
        provider_status: 'ACCEPTED',
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        meta: ['source' => 'runtime'],
    );

    expect($record->intent_key)->toBe('claim.succeeded.claimant')
        ->and($record->channel)->toBe('null')
        ->and($record->recipient->type)->toBe('claimant')
        ->and($record->status)->toBe('sent')
        ->and($record->provider_message_id)->toBe('provider-message-1')
        ->and($record->provider_status)->toBe('ACCEPTED')
        ->and($record->correlation_id)->toBe('execution-1')
        ->and($record->causation_id)->toBe('journal-1')
        ->and($record->meta)->toBe(['source' => 'runtime']);
});

it('records delivery attempt results through the recorder seam', function () {
    $attempt = app(FeedbackDeliveryAttemptRuntimeContract::class)->execute(
        app(FeedbackDispatchPreparerContract::class)->prepare(feedbackRecordingIntent(
            recipients: [
                new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
                new FeedbackRecipientData(type: 'issuer', id: 'issuer-1', email: 'issuer@example.test'),
            ],
        )),
    );

    $records = app(FeedbackDeliveryAttemptRecorderContract::class)->record($attempt);

    expect($records)->toHaveCount(2)
        ->and($records[0])->toBeInstanceOf(FeedbackDeliveryRecordData::class)
        ->and($records[0]->intent_key)->toBe('claim.succeeded.claimant')
        ->and($records[0]->channel)->toBe('null')
        ->and($records[0]->status)->toBe('sent')
        ->and($records[0]->correlation_id)->toBe('execution-1')
        ->and($records[1]->recipient->type)->toBe('issuer');
});

it('keeps in-memory delivery records queryable by correlation id and intent key', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);
    $attempt = app(FeedbackDeliveryAttemptRuntimeContract::class)->execute(
        app(FeedbackDispatchPreparerContract::class)->prepare(feedbackRecordingIntent()),
    );

    $recorder->record($attempt);

    expect($recorder->forCorrelation('execution-1'))->toHaveCount(1)
        ->and($recorder->forIntent('claim.succeeded.claimant'))->toHaveCount(1)
        ->and($recorder->all())->toHaveCount(1);
});

it('can reset the in-memory recorder without mutating attempt data', function () {
    $recorder = app(FeedbackDeliveryAttemptRecorderContract::class);
    $attempt = app(FeedbackDeliveryAttemptRuntimeContract::class)->execute(
        app(FeedbackDispatchPreparerContract::class)->prepare(feedbackRecordingIntent()),
    );

    $recorder->record($attempt);
    $recorder->reset();

    expect($recorder->all())->toBe([])
        ->and($attempt->deliveries)->toHaveCount(1);
});

it('binds the non-persistent delivery attempt recorder for package consumers', function () {
    expect(app(FeedbackDeliveryAttemptRecorderContract::class))->toBeInstanceOf(InMemoryFeedbackDeliveryAttemptRecorder::class)
        ->and(app(FeedbackDeliveryAttemptRecorderContract::class))->toBe(app(FeedbackDeliveryAttemptRecorderContract::class));
});

it('keeps delivery recording baseline independent from persistence journal routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackRecordingIntent(array $recipients = [], array $channels = []): FeedbackIntentData
{
    return FeedbackIntentData::forEvent(
        key: 'claim.succeeded.claimant',
        eventType: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim approved', body: 'Your claim was approved.'),
        recipients: $recipients ?: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
        ],
        channels: $channels ?: [
            new FeedbackChannelData(key: 'null'),
        ],
        source: 'x-change',
        correlationId: 'execution-1',
        causationId: 'journal-1',
        subjectType: 'claim',
        subjectId: 'claim-1',
    );
}
