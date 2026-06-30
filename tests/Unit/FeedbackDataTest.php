<?php

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackContextData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

it('models feedback intent as the communication boundary object', function () {
    $intent = new FeedbackIntentData(
        key: 'claim.succeeded.claimant',
        message: new FeedbackMessageData(
            title: 'Claim approved',
            body: 'Your claim was approved.',
            actions: [
                ['key' => 'claim.view', 'label' => 'View claim'],
            ],
            artifacts: [
                ['type' => 'receipt', 'id' => 'receipt-1'],
            ],
        ),
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', name: 'Ana', email: 'ana@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'null'),
        ],
        context: new FeedbackContextData(
            event_type: 'claim.succeeded',
            source: 'x-change',
            correlation_id: 'execution-1',
            causation_id: 'journal-1',
            subject_type: 'claim',
            subject_id: 'claim-1',
        ),
        meta: ['feature_profile' => 'default'],
    );

    expect($intent->key)->toBe('claim.succeeded.claimant')
        ->and($intent->message->actions)->toBe([['key' => 'claim.view', 'label' => 'View claim']])
        ->and($intent->message->artifacts)->toBe([['type' => 'receipt', 'id' => 'receipt-1']])
        ->and($intent->recipients[0]->email)->toBe('ana@example.test')
        ->and($intent->channels[0]->key)->toBe('null')
        ->and($intent->context->event_type)->toBe('claim.succeeded')
        ->and($intent->meta)->toBe(['feature_profile' => 'default']);
});

it('models explicit delivery states instead of booleans', function () {
    $delivery = new FeedbackDeliveryData(
        intent_key: 'claim.succeeded.claimant',
        channel: 'null',
        recipient: new FeedbackRecipientData(type: 'claimant', id: 'user-1'),
        status: FeedbackDeliveryData::StatusPending,
    );

    expect(FeedbackDeliveryData::statuses())->toContain(FeedbackDeliveryData::StatusPending)
        ->and(FeedbackDeliveryData::statuses())->toContain(FeedbackDeliveryData::StatusSent)
        ->and(FeedbackDeliveryData::statuses())->toContain(FeedbackDeliveryData::StatusFailedRetryable)
        ->and(FeedbackDeliveryData::statuses())->toContain(FeedbackDeliveryData::StatusExpired)
        ->and($delivery->status)->toBe('pending');
});

