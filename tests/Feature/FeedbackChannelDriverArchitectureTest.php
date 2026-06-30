<?php

use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackChannelHealthData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Drivers\InAppFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\LogFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\MailFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\NullFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\WebhookFeedbackChannelDriver;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackChannelException;

it('resolves the baseline channel drivers from the package registry', function (string $channel, string $driverClass) {
    expect(app(FeedbackChannelRegistryContract::class)->driver($channel))
        ->toBeInstanceOf($driverClass)
        ->toBeInstanceOf(FeedbackChannelDriverContract::class);
})->with([
    'null' => ['null', NullFeedbackChannelDriver::class],
    'log' => ['log', LogFeedbackChannelDriver::class],
    'in_app' => ['in_app', InAppFeedbackChannelDriver::class],
    'mail' => ['mail', MailFeedbackChannelDriver::class],
    'webhook' => ['webhook', WebhookFeedbackChannelDriver::class],
]);

it('fails closed for unknown channel drivers', function () {
    app(FeedbackChannelRegistryContract::class)->driver('imaginary');
})->throws(UnknownFeedbackChannelException::class);

it('exposes health checks for all baseline drivers', function (string $channel) {
    $health = app(FeedbackChannelRegistryContract::class)->driver($channel)->health();

    expect($health)->toBeInstanceOf(FeedbackChannelHealthData::class)
        ->and($health->channel)->toBe($channel)
        ->and($health->healthy)->toBeTrue()
        ->and($health->status)->toBe(FeedbackChannelHealthData::StatusAvailable);
})->with([
    'null',
    'log',
    'in_app',
    'mail',
    'webhook',
]);

it('reports whether a baseline driver supports the supplied recipient and channel context', function () {
    $intent = feedbackDriverIntent();
    $emailRecipient = new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test');
    $recipientWithoutEmail = new FeedbackRecipientData(type: 'claimant', id: 'user-1');

    expect(app(FeedbackChannelRegistryContract::class)->driver('null')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'null')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('log')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'log')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('in_app')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'in_app')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('mail')->supports($intent, $emailRecipient, new FeedbackChannelData(key: 'mail')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('mail')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'mail')))->toBeFalse()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('webhook')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'webhook', options: ['url' => 'https://example.test/feedback'])))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('webhook')->supports($intent, $recipientWithoutEmail, new FeedbackChannelData(key: 'webhook')))->toBeFalse();
});

it('keeps baseline driver sends as package-local handoff facts without provider side effects', function (string $channel, FeedbackRecipientData $recipient, FeedbackChannelData $channelData, string $expectedStatus) {
    $delivery = app(FeedbackChannelRegistryContract::class)
        ->driver($channel)
        ->send(feedbackDriverIntent(), $recipient, $channelData);

    expect($delivery)->toBeInstanceOf(FeedbackDeliveryData::class)
        ->and($delivery->intent_key)->toBe('claim.succeeded.claimant')
        ->and($delivery->channel)->toBe($channel)
        ->and($delivery->status)->toBe($expectedStatus)
        ->and($delivery->provider_message_id)->toBeNull()
        ->and($delivery->result['driver'])->toBe($channel)
        ->and($delivery->meta['feedback_only'])->toBeTrue()
        ->and($delivery->meta['provider_side_effect'])->toBeFalse();
})->with([
    'null' => ['null', new FeedbackRecipientData(type: 'claimant', id: 'user-1'), new FeedbackChannelData(key: 'null'), FeedbackDeliveryData::StatusSent],
    'log' => ['log', new FeedbackRecipientData(type: 'claimant', id: 'user-1'), new FeedbackChannelData(key: 'log'), FeedbackDeliveryData::StatusQueued],
    'in_app' => ['in_app', new FeedbackRecipientData(type: 'claimant', id: 'user-1'), new FeedbackChannelData(key: 'in_app'), FeedbackDeliveryData::StatusQueued],
    'mail' => ['mail', new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'), new FeedbackChannelData(key: 'mail'), FeedbackDeliveryData::StatusQueued],
]);

it('keeps channel driver architecture independent from provider sdks persistence queues routes and host packages', function () {
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

function feedbackDriverIntent(): FeedbackIntentData
{
    return FeedbackIntentData::forEvent(
        key: 'claim.succeeded.claimant',
        eventType: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim approved', body: 'Your claim was approved.'),
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
        ],
        channels: [
            new FeedbackChannelData(key: 'null'),
        ],
        source: 'x-change',
        correlationId: 'execution-1',
        causationId: 'journal-1',
        subjectType: 'claim',
        subjectId: 'claim-1',
    );
}
