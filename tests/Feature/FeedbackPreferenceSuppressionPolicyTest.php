<?php

use LBHurtado\XFeedback\Contracts\FeedbackSuppressionEvaluatorContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackNotificationPreferenceData;
use LBHurtado\XFeedback\Data\FeedbackQuietHoursData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionDecisionData;
use LBHurtado\XFeedback\Data\FeedbackSuppressionPolicyData;
use LBHurtado\XFeedback\Services\FeedbackSuppressionEvaluator;

it('models notification preferences and suppression policies without persistence concerns', function () {
    $preference = new FeedbackNotificationPreferenceData(
        event_key: 'claim.succeeded',
        channel: 'sms',
        enabled: false,
        recipient_type: 'claimant',
        recipient_id: 'user-1',
        meta: ['source' => 'profile'],
    );

    $quietHours = new FeedbackQuietHoursData(
        start_time: '22:00',
        end_time: '07:00',
        timezone: 'Asia/Manila',
        channels: ['sms'],
    );

    $policy = new FeedbackSuppressionPolicyData(
        preferences: [$preference],
        quiet_hours: [$quietHours],
        opt_out_channels: ['webhook'],
        disabled_channels: ['slack'],
        required_channels: ['email'],
        stale_after_seconds: 300,
        meta: ['profile' => 'default'],
    );

    expect($policy->preferences)->toBe([$preference])
        ->and($policy->quiet_hours)->toBe([$quietHours])
        ->and($policy->opt_out_channels)->toBe(['webhook'])
        ->and($policy->disabled_channels)->toBe(['slack'])
        ->and($policy->required_channels)->toBe(['email'])
        ->and($policy->stale_after_seconds)->toBe(300)
        ->and($policy->meta)->toBe(['profile' => 'default']);
});

it('suppresses channels disabled by recipient notification preference', function () {
    $decision = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(),
        recipient: feedbackSuppressionRecipient(),
        channel: new FeedbackChannelData(key: 'sms'),
        policy: new FeedbackSuppressionPolicyData(preferences: [
            new FeedbackNotificationPreferenceData(
                event_key: 'claim.succeeded',
                channel: 'sms',
                enabled: false,
                recipient_type: 'claimant',
                recipient_id: 'user-1',
            ),
        ]),
        now: '2026-06-30T12:00:00+08:00',
    );

    expect($decision)->toBeInstanceOf(FeedbackSuppressionDecisionData::class)
        ->and($decision->allowed)->toBeFalse()
        ->and($decision->suppressed)->toBeTrue()
        ->and($decision->reason)->toBe(FeedbackSuppressionDecisionData::ReasonPreferenceDisabled)
        ->and($decision->channel)->toBe('sms')
        ->and($decision->recipient_id)->toBe('user-1');
});

it('suppresses recipient opt-out channels before delivery planning becomes provider work', function () {
    $decision = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(),
        recipient: feedbackSuppressionRecipient(),
        channel: 'webhook',
        policy: new FeedbackSuppressionPolicyData(opt_out_channels: ['webhook']),
        now: '2026-06-30T12:00:00+08:00',
    );

    expect($decision->allowed)->toBeFalse()
        ->and($decision->suppressed)->toBeTrue()
        ->and($decision->reason)->toBe(FeedbackSuppressionDecisionData::ReasonOptedOut);
});

it('suppresses non-required channel delivery during quiet hours', function () {
    $decision = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(),
        recipient: feedbackSuppressionRecipient(),
        channel: 'sms',
        policy: new FeedbackSuppressionPolicyData(quiet_hours: [
            new FeedbackQuietHoursData(
                start_time: '22:00',
                end_time: '07:00',
                timezone: 'Asia/Manila',
                channels: ['sms'],
            ),
        ]),
        now: '2026-06-30T23:15:00+08:00',
    );

    expect($decision->allowed)->toBeFalse()
        ->and($decision->suppressed)->toBeTrue()
        ->and($decision->reason)->toBe(FeedbackSuppressionDecisionData::ReasonQuietHours)
        ->and($decision->meta['quiet_hours_timezone'])->toBe('Asia/Manila');
});

it('suppresses expired and stale intents as freshness-aware non-delivery gates', function () {
    $expired = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(expiresAt: '2026-06-30T11:59:00+08:00'),
        recipient: feedbackSuppressionRecipient(),
        channel: 'email',
        policy: new FeedbackSuppressionPolicyData,
        now: '2026-06-30T12:00:00+08:00',
    );

    $stale = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(meta: ['created_at' => '2026-06-30T11:50:00+08:00']),
        recipient: feedbackSuppressionRecipient(),
        channel: 'email',
        policy: new FeedbackSuppressionPolicyData(stale_after_seconds: 300),
        now: '2026-06-30T12:00:01+08:00',
    );

    expect($expired->allowed)->toBeFalse()
        ->and($expired->reason)->toBe(FeedbackSuppressionDecisionData::ReasonExpired)
        ->and($stale->allowed)->toBeFalse()
        ->and($stale->reason)->toBe(FeedbackSuppressionDecisionData::ReasonStale);
});

it('marks required channels as allowed policy decisions without overriding hard suppression gates', function () {
    $allowed = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(),
        recipient: feedbackSuppressionRecipient(),
        channel: 'email',
        policy: new FeedbackSuppressionPolicyData(required_channels: ['email']),
        now: '2026-06-30T12:00:00+08:00',
    );

    $suppressed = app(FeedbackSuppressionEvaluatorContract::class)->evaluate(
        intent: feedbackSuppressionIntent(),
        recipient: feedbackSuppressionRecipient(),
        channel: 'email',
        policy: new FeedbackSuppressionPolicyData(required_channels: ['email'], disabled_channels: ['email']),
        now: '2026-06-30T12:00:00+08:00',
    );

    expect($allowed->allowed)->toBeTrue()
        ->and($allowed->suppressed)->toBeFalse()
        ->and($allowed->reason)->toBe(FeedbackSuppressionDecisionData::ReasonRequired)
        ->and($allowed->meta['required'])->toBeTrue()
        ->and($suppressed->allowed)->toBeFalse()
        ->and($suppressed->reason)->toBe(FeedbackSuppressionDecisionData::ReasonDisabledChannel);
});

it('binds the suppression evaluator for package consumers', function () {
    expect(app(FeedbackSuppressionEvaluatorContract::class))->toBeInstanceOf(FeedbackSuppressionEvaluator::class)
        ->and(app(FeedbackSuppressionEvaluatorContract::class))->toBe(app(FeedbackSuppressionEvaluatorContract::class));
});

it('keeps preference and suppression policy independent from delivery persistence routes providers and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeTrue()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeTrue()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackSuppressionIntent(?string $expiresAt = null, array $meta = []): FeedbackIntentData
{
    return new FeedbackIntentData(
        key: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim succeeded', body: 'Your claim succeeded.'),
        expires_at: $expiresAt,
        meta: $meta,
    );
}

function feedbackSuppressionRecipient(): FeedbackRecipientData
{
    return new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'user@example.test', phone: '+639171234567');
}
