<?php

use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatcherContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\NullFeedbackChannelDriver;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackChannelException;
use LBHurtado\XFeedback\Services\FeedbackDispatcher;

it('dispatches feedback intents through registered channel drivers', function () {
    $intent = feedbackIntent(
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
            new FeedbackRecipientData(type: 'issuer', id: 'issuer-1', email: 'issuer@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'null'),
        ],
    );

    $deliveries = app(FeedbackDispatcherContract::class)->dispatch($intent);

    expect($deliveries)->toHaveCount(2)
        ->and($deliveries[0])->toBeInstanceOf(FeedbackDeliveryData::class)
        ->and($deliveries[0]->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($deliveries[0]->intent_key)->toBe('claim.succeeded.claimant')
        ->and($deliveries[0]->channel)->toBe('null')
        ->and($deliveries[0]->correlation_id)->toBe('execution-1')
        ->and($deliveries[0]->causation_id)->toBe('journal-1')
        ->and($deliveries[1]->recipient->type)->toBe('issuer');
});

it('fails closed for unknown feedback channels before dispatching delivery', function () {
    app(FeedbackDispatcherContract::class)->dispatch(feedbackIntent(channels: [
        new FeedbackChannelData(key: 'sms'),
    ]));
})->throws(UnknownFeedbackChannelException::class);

it('binds dispatcher registry and null channel driver for package consumers', function () {
    expect(app(FeedbackDispatcherContract::class))->toBeInstanceOf(FeedbackDispatcher::class)
        ->and(app(FeedbackDispatcherContract::class))->toBe(app(FeedbackDispatcherContract::class))
        ->and(app(FeedbackChannelRegistryContract::class)->driver('null'))->toBeInstanceOf(NullFeedbackChannelDriver::class);
});

it('keeps feedback dispatch independent from lifecycle truth execution actions persistence and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackIntent(array $recipients = [], array $channels = []): FeedbackIntentData
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

