<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use LBHurtado\SMS\Facades\SMS;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackWebhookSenderContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackWebhookMessageData;
use LBHurtado\XFeedback\Data\FeedbackWebhookSendResultData;
use LBHurtado\XFeedback\Drivers\EmailFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\SmsFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\WebhookFeedbackChannelDriver;
use LBHurtado\XFeedback\Mail\FeedbackEmailMessage;
use LBHurtado\XFeedback\Services\SpatieFeedbackWebhookSender;
use Spatie\WebhookServer\CallWebhookJob;

it('resolves explicit email sms and webhook transport drivers', function (string $channel, string $driverClass) {
    expect(app(FeedbackChannelRegistryContract::class)->driver($channel))->toBeInstanceOf($driverClass);
})->with([
    'email' => ['email', EmailFeedbackChannelDriver::class],
    'sms' => ['sms', SmsFeedbackChannelDriver::class],
    'webhook' => ['webhook', WebhookFeedbackChannelDriver::class],
]);

it('sends email feedback through Laravel mail', function () {
    Mail::fake();

    $delivery = app(FeedbackChannelRegistryContract::class)
        ->driver('email')
        ->send(
            feedbackTransportIntent(),
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'),
            new FeedbackChannelData(key: 'email'),
        );

    Mail::assertSent(FeedbackEmailMessage::class, function (FeedbackEmailMessage $mail): bool {
        return $mail->hasTo('user@example.test')
            && $mail->intent->key === 'claim.succeeded.claimant'
            && $mail->recipient->id === 'user-1';
    });

    expect($delivery)->toBeInstanceOf(FeedbackDeliveryData::class)
        ->and($delivery->channel)->toBe('email')
        ->and($delivery->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($delivery->result['driver'])->toBe('email')
        ->and($delivery->result['transport'])->toBe('laravel_mail')
        ->and($delivery->meta['provider_side_effect'])->toBeTrue();
});

it('sends sms feedback through the lbhurtado sms facade', function () {
    SMS::shouldReceive('channel')->once()->with('engagespark')->andReturnSelf();
    SMS::shouldReceive('from')->once()->with('XCHANGE')->andReturnSelf();
    SMS::shouldReceive('to')->once()->with('+639171234567')->andReturnSelf();
    SMS::shouldReceive('content')->once()->with('Your claim was approved.')->andReturnSelf();
    SMS::shouldReceive('send')->once()->andReturn(['message_id' => 'sms-message-1']);

    $delivery = app(FeedbackChannelRegistryContract::class)
        ->driver('sms')
        ->send(
            feedbackTransportIntent(),
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'),
            new FeedbackChannelData(key: 'sms', options: [
                'driver' => 'engagespark',
                'sender' => 'XCHANGE',
            ]),
        );

    expect($delivery)->toBeInstanceOf(FeedbackDeliveryData::class)
        ->and($delivery->channel)->toBe('sms')
        ->and($delivery->status)->toBe(FeedbackDeliveryData::StatusSent)
        ->and($delivery->provider_message_id)->toBe('sms-message-1')
        ->and($delivery->result['driver'])->toBe('sms')
        ->and($delivery->result['transport'])->toBe('lbhurtado_sms')
        ->and($delivery->meta['provider_side_effect'])->toBeTrue();
});

it('dispatches webhook feedback through the internal webhook sender seam', function () {
    $fakeSender = new RecordingFeedbackWebhookSender;
    app()->instance(FeedbackWebhookSenderContract::class, $fakeSender);

    $delivery = app(FeedbackChannelRegistryContract::class)
        ->driver('webhook')
        ->send(
            feedbackTransportIntent(),
            new FeedbackRecipientData(type: 'system', id: 'settlement-os'),
            new FeedbackChannelData(key: 'webhook', options: [
                'url' => 'https://example.test/feedback',
                'secret' => 'webhook-secret',
                'headers' => ['X-Feedback-Test' => 'yes'],
            ]),
        );

    expect($fakeSender->sent)->toHaveCount(1)
        ->and($fakeSender->sent[0]->url)->toBe('https://example.test/feedback')
        ->and($fakeSender->sent[0]->payload['intent_key'])->toBe('claim.succeeded.claimant')
        ->and($fakeSender->sent[0]->payload['message']['body'])->toBe('Your claim was approved.')
        ->and($fakeSender->sent[0]->headers['X-Feedback-Test'])->toBe('yes')
        ->and($fakeSender->sent[0]->secret)->toBe('webhook-secret')
        ->and($fakeSender->sent[0]->meta['source'])->toBe('x-feedback');

    expect($delivery)->toBeInstanceOf(FeedbackDeliveryData::class)
        ->and($delivery->channel)->toBe('webhook')
        ->and($delivery->status)->toBe(FeedbackDeliveryData::StatusQueued)
        ->and($delivery->provider_message_id)->toBe('webhook-message-1')
        ->and($delivery->result['driver'])->toBe('webhook')
        ->and($delivery->result['transport'])->toBe('recording_webhook_sender')
        ->and($delivery->meta['provider_side_effect'])->toBeTrue();
});

it('wraps spatie webhook server behind the default webhook sender', function () {
    Bus::fake();

    app(SpatieFeedbackWebhookSender::class)->send(new FeedbackWebhookMessageData(
        url: 'https://example.test/feedback',
        payload: [
            'intent_key' => 'claim.succeeded.claimant',
            'message' => ['body' => 'Your claim was approved.'],
        ],
        headers: ['X-Feedback-Test' => 'yes'],
        secret: 'webhook-secret',
        message_id: 'webhook-message-1',
        meta: ['source' => 'x-feedback'],
    ));

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job): bool {
        return $job->webhookUrl === 'https://example.test/feedback'
            && $job->payload['intent_key'] === 'claim.succeeded.claimant'
            && $job->payload['message']['body'] === 'Your claim was approved.'
            && $job->headers['X-Feedback-Test'] === 'yes'
            && $job->meta['source'] === 'x-feedback'
            && $job->uuid === 'webhook-message-1';
    });
});

it('reports transport support requirements for email sms and webhook', function () {
    $intent = feedbackTransportIntent();

    expect(app(FeedbackChannelRegistryContract::class)->driver('email')->supports($intent, new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test'), new FeedbackChannelData(key: 'email')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('email')->supports($intent, new FeedbackRecipientData(type: 'claimant', id: 'user-1'), new FeedbackChannelData(key: 'email')))->toBeFalse()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('sms')->supports($intent, new FeedbackRecipientData(type: 'claimant', id: 'user-1', phone: '+639171234567'), new FeedbackChannelData(key: 'sms')))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('sms')->supports($intent, new FeedbackRecipientData(type: 'claimant', id: 'user-1'), new FeedbackChannelData(key: 'sms')))->toBeFalse()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('webhook')->supports($intent, new FeedbackRecipientData(type: 'system', id: 'settlement-os'), new FeedbackChannelData(key: 'webhook', options: ['url' => 'https://example.test/feedback'])))->toBeTrue()
        ->and(app(FeedbackChannelRegistryContract::class)->driver('webhook')->supports($intent, new FeedbackRecipientData(type: 'system', id: 'settlement-os'), new FeedbackChannelData(key: 'webhook')))->toBeFalse();
});

it('keeps transport driver baseline independent from persistence routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackTransportIntent(): FeedbackIntentData
{
    return FeedbackIntentData::forEvent(
        key: 'claim.succeeded.claimant',
        eventType: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim approved', body: 'Your claim was approved.'),
        recipients: [
            new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test', phone: '+639171234567'),
        ],
        channels: [
            new FeedbackChannelData(key: 'email'),
            new FeedbackChannelData(key: 'sms'),
            new FeedbackChannelData(key: 'webhook', options: ['url' => 'https://example.test/feedback']),
        ],
        source: 'x-change',
        correlationId: 'execution-1',
        causationId: 'journal-1',
        subjectType: 'claim',
        subjectId: 'claim-1',
    );
}

final class RecordingFeedbackWebhookSender implements FeedbackWebhookSenderContract
{
    /**
     * @var array<int, FeedbackWebhookMessageData>
     */
    public array $sent = [];

    public function send(FeedbackWebhookMessageData $message): FeedbackWebhookSendResultData
    {
        $this->sent[] = $message;

        return new FeedbackWebhookSendResultData(
            message_id: 'webhook-message-1',
            status: FeedbackDeliveryData::StatusQueued,
            result: [
                'transport' => 'recording_webhook_sender',
                'url' => $message->url,
            ],
        );
    }
}
