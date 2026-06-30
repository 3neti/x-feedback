<?php

use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackDeliveryAttemptRuntimeContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackChannelException;
use LBHurtado\XFeedback\Services\FeedbackDeliveryAttemptRuntime;

it('executes prepared delivery plan items through registered channel drivers', function () {
    app(FeedbackChannelRegistryContract::class)->register('test', new RecordingFeedbackChannelDriver);

    $preparation = app(FeedbackDispatchPreparerContract::class)->prepare(feedbackAttemptIntent(
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
            new FeedbackRecipientData(type: 'issuer', id: 'issuer-1', email: 'issuer@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'test'),
        ],
    ));

    $attempt = app(FeedbackDeliveryAttemptRuntimeContract::class)->execute($preparation);

    expect($attempt)->toBeInstanceOf(FeedbackDeliveryAttemptData::class)
        ->and($attempt->intent_key)->toBe('claim.succeeded.claimant')
        ->and($attempt->status)->toBe(FeedbackDeliveryAttemptData::StatusCompleted)
        ->and($attempt->deliveries)->toHaveCount(2)
        ->and($attempt->receipts)->toHaveCount(2)
        ->and($attempt->deliveries[0]->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($attempt->deliveries[0]->channel)->toBe('test')
        ->and($attempt->deliveries[0]->recipient->type)->toBe('claimant')
        ->and($attempt->receipts[0]->provider_status)->toBe('ACCEPTED')
        ->and($attempt->correlation_id)->toBe('execution-1')
        ->and($attempt->causation_id)->toBe('journal-1');
});

it('executes prepared null-channel plans through the default null driver', function () {
    $attempt = app(FeedbackDeliveryAttemptRuntimeContract::class)->execute(
        app(FeedbackDispatchPreparerContract::class)->prepare(feedbackAttemptIntent()),
    );

    expect($attempt->status)->toBe(FeedbackDeliveryAttemptData::StatusCompleted)
        ->and($attempt->deliveries)->toHaveCount(1)
        ->and($attempt->deliveries[0]->channel)->toBe('null')
        ->and($attempt->deliveries[0]->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($attempt->receipts[0]->status)->toBe(FeedbackDeliveryData::StatusSent);
});

it('fails closed for unknown planned delivery channels before later plan items are sent', function () {
    app(FeedbackChannelRegistryContract::class)->register('test', new RecordingFeedbackChannelDriver);
    RecordingFeedbackChannelDriver::$sent = 0;

    $preparation = app(FeedbackDispatchPreparerContract::class)->prepare(feedbackAttemptIntent(channels: [
        new FeedbackChannelData(key: 'unknown', priority: 10),
        new FeedbackChannelData(key: 'test', priority: 20),
    ]));

    try {
        app(FeedbackDeliveryAttemptRuntimeContract::class)->execute($preparation);
    } catch (UnknownFeedbackChannelException $exception) {
        expect(RecordingFeedbackChannelDriver::$sent)->toBe(0);

        throw $exception;
    }
})->throws(UnknownFeedbackChannelException::class);

it('binds the delivery attempt runtime for package consumers', function () {
    expect(app(FeedbackDeliveryAttemptRuntimeContract::class))->toBeInstanceOf(FeedbackDeliveryAttemptRuntime::class)
        ->and(app(FeedbackDeliveryAttemptRuntimeContract::class))->toBe(app(FeedbackDeliveryAttemptRuntimeContract::class));
});

it('keeps delivery attempt runtime independent from persistence queues routes and host packages', function () {
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

function feedbackAttemptIntent(array $recipients = [], array $channels = []): FeedbackIntentData
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

final class RecordingFeedbackChannelDriver implements FeedbackChannelDriverContract
{
    public static int $sent = 0;

    public function send(
        FeedbackIntentData $intent,
        FeedbackRecipientData $recipient,
        FeedbackChannelData $channel,
    ): FeedbackDeliveryData {
        self::$sent++;

        return new FeedbackDeliveryData(
            intent_key: $intent->key,
            channel: $channel->key,
            recipient: $recipient,
            status: FeedbackDeliveryData::StatusSent,
            provider_message_id: 'provider-message-'.self::$sent,
            result: ['provider_status' => 'ACCEPTED'],
            correlation_id: $intent->context?->correlation_id,
            causation_id: $intent->context?->causation_id,
            meta: ['runtime_test' => true],
        );
    }
}
