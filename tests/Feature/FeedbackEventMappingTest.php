<?php

use LBHurtado\XFeedback\Contracts\FeedbackEventMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackEventMapperRegistryContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackEventMapperException;
use LBHurtado\XFeedback\Services\FeedbackEventMapperRegistry;

it('models generic feedback events without owning lifecycle meaning', function () {
    $event = new FeedbackEventData(
        type: 'claim.succeeded',
        source: 'x-change',
        payload: ['claim_id' => 'claim-1', 'claimant_id' => 'user-1'],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        subject_type: 'claim',
        subject_id: 'claim-1',
        actor_type: 'system',
        actor_id: 'workflow',
        occurred_at: '2026-06-30T12:00:00+08:00',
        meta: ['feature_profile' => 'default'],
    );

    expect($event->type)->toBe('claim.succeeded')
        ->and($event->payload)->toBe(['claim_id' => 'claim-1', 'claimant_id' => 'user-1'])
        ->and($event->correlation_id)->toBe('execution-1')
        ->and($event->causation_id)->toBe('journal-1')
        ->and($event->subject_type)->toBe('claim')
        ->and($event->subject_id)->toBe('claim-1')
        ->and($event->meta)->toBe(['feature_profile' => 'default']);
});

it('maps registered feedback events into feedback intents', function () {
    app(FeedbackEventMapperRegistryContract::class)->register('claim.succeeded', new ClaimSucceededFeedbackMapper);

    $intent = app(FeedbackEventMapperRegistryContract::class)->map(new FeedbackEventData(
        type: 'claim.succeeded',
        source: 'x-change',
        payload: [
            'claimant_id' => 'user-1',
            'claimant_email' => 'user@example.test',
        ],
        correlation_id: 'execution-1',
        causation_id: 'journal-1',
        subject_type: 'claim',
        subject_id: 'claim-1',
    ));

    expect($intent)->toBeInstanceOf(FeedbackIntentData::class)
        ->and($intent->key)->toBe('claim.succeeded.claimant')
        ->and($intent->message->title)->toBe('Claim approved')
        ->and($intent->recipients[0]->type)->toBe('claimant')
        ->and($intent->recipients[0]->id)->toBe('user-1')
        ->and($intent->recipients[0]->email)->toBe('user@example.test')
        ->and($intent->channels[0]->key)->toBe('null')
        ->and($intent->context->event_type)->toBe('claim.succeeded')
        ->and($intent->context->source)->toBe('x-change')
        ->and($intent->context->correlation_id)->toBe('execution-1')
        ->and($intent->context->causation_id)->toBe('journal-1')
        ->and($intent->context->subject_type)->toBe('claim')
        ->and($intent->context->subject_id)->toBe('claim-1');
});

it('resolves mapper class strings through the container', function () {
    app(FeedbackEventMapperRegistryContract::class)->register('claim.failed', ClaimFailedFeedbackMapper::class);

    $intent = app(FeedbackEventMapperRegistryContract::class)->map(new FeedbackEventData(
        type: 'claim.failed',
        payload: [
            'claimant_id' => 'user-1',
            'claimant_email' => 'user@example.test',
            'reason' => 'Provider unavailable',
        ],
    ));

    expect($intent->key)->toBe('claim.failed.claimant')
        ->and($intent->message->title)->toBe('Claim could not be completed')
        ->and($intent->message->body)->toContain('Provider unavailable');
});

it('fails closed for unmapped feedback events before delivery dispatch', function () {
    app(FeedbackEventMapperRegistryContract::class)->map(new FeedbackEventData(type: 'claim.unknown'));
})->throws(UnknownFeedbackEventMapperException::class);

it('binds the feedback event mapper registry for package consumers', function () {
    expect(app(FeedbackEventMapperRegistryContract::class))->toBeInstanceOf(FeedbackEventMapperRegistry::class)
        ->and(app(FeedbackEventMapperRegistryContract::class))->toBe(app(FeedbackEventMapperRegistryContract::class));
});

it('keeps event mapping independent from delivery persistence routes actions journal and lifecycle truth', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

final class ClaimSucceededFeedbackMapper implements FeedbackEventMapperContract
{
    public function map(FeedbackEventData $event): FeedbackIntentData
    {
        return FeedbackIntentData::forEvent(
            key: 'claim.succeeded.claimant',
            eventType: $event->type,
            message: new FeedbackMessageData(
                title: 'Claim approved',
                body: 'Your claim was approved.',
            ),
            recipients: [
                new FeedbackRecipientData(
                    type: 'claimant',
                    id: $event->payload['claimant_id'] ?? null,
                    email: $event->payload['claimant_email'] ?? null,
                ),
            ],
            channels: [
                new FeedbackChannelData(key: 'null'),
            ],
            source: $event->source,
            correlationId: $event->correlation_id,
            causationId: $event->causation_id,
            subjectType: $event->subject_type,
            subjectId: $event->subject_id,
        );
    }
}

final class ClaimFailedFeedbackMapper implements FeedbackEventMapperContract
{
    public function map(FeedbackEventData $event): FeedbackIntentData
    {
        return FeedbackIntentData::forEvent(
            key: 'claim.failed.claimant',
            eventType: $event->type,
            message: new FeedbackMessageData(
                title: 'Claim could not be completed',
                body: 'Your claim could not be completed: '.($event->payload['reason'] ?? 'Unknown reason'),
            ),
            recipients: [
                new FeedbackRecipientData(
                    type: 'claimant',
                    id: $event->payload['claimant_id'] ?? null,
                    email: $event->payload['claimant_email'] ?? null,
                ),
            ],
            channels: [
                new FeedbackChannelData(key: 'null'),
            ],
        );
    }
}
