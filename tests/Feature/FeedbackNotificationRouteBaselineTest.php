<?php

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Contracts\FeedbackNotificationRouteResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackNotificationRouteData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Services\FeedbackNotificationRouteResolver;

it('models notification routes without hardcoded recipient channel fields', function () {
    $route = new FeedbackNotificationRouteData(
        notifiable_type: 'claimant',
        notifiable_id: 'user-1',
        channel: 'sms',
        address: '+639171234567',
        verified_at: '2026-07-01T08:00:00+08:00',
        is_primary: true,
        priority: 10,
        meta: ['source' => 'profile'],
    );

    expect($route->notifiable_type)->toBe('claimant')
        ->and($route->notifiable_id)->toBe('user-1')
        ->and($route->channel)->toBe('sms')
        ->and($route->address)->toBe('+639171234567')
        ->and($route->verified_at)->toBe('2026-07-01T08:00:00+08:00')
        ->and($route->is_primary)->toBeTrue()
        ->and($route->priority)->toBe(10)
        ->and($route->meta)->toBe(['source' => 'profile']);
});

it('resolves primary verified notification routes from recipient route data', function () {
    $recipient = feedbackRouteRecipient([
        'sms' => [
            ['address' => '+639170000002', 'verified_at' => null, 'is_primary' => false, 'priority' => 10],
            ['address' => '+639170000001', 'verified_at' => '2026-07-01T08:00:00+08:00', 'is_primary' => true, 'priority' => 50],
        ],
    ]);

    $route = app(FeedbackNotificationRouteResolverContract::class)->resolve($recipient, 'sms');

    expect($route)->toBeInstanceOf(FeedbackNotificationRouteData::class)
        ->and($route->address)->toBe('+639170000001')
        ->and($route->is_primary)->toBeTrue()
        ->and($route->verified_at)->toBe('2026-07-01T08:00:00+08:00');
});

it('resolves all notification routes in deterministic primary verified priority order', function () {
    $recipient = feedbackRouteRecipient([
        'email' => [
            ['address' => 'fallback@example.test', 'priority' => 10],
            ['address' => 'primary@example.test', 'is_primary' => true, 'priority' => 90],
            ['address' => 'verified@example.test', 'verified_at' => '2026-07-01T08:00:00+08:00', 'priority' => 1],
        ],
    ]);

    $routes = app(FeedbackNotificationRouteResolverContract::class)->resolveAll($recipient, 'email');

    expect(array_map(fn (FeedbackNotificationRouteData $route): string => $route->address, $routes))
        ->toBe(['primary@example.test', 'verified@example.test', 'fallback@example.test']);
});

it('resolves config-backed notification routes for package consumers without a database route book', function () {
    config()->set('x-feedback.notification_routes', [
        [
            'notifiable_type' => 'external_endpoint',
            'notifiable_id' => 'endpoint-1',
            'channel' => 'webhook',
            'address' => 'https://example.test/webhook/fallback',
            'priority' => 50,
        ],
        [
            'notifiable_type' => 'external_endpoint',
            'notifiable_id' => 'endpoint-1',
            'channel' => 'webhook',
            'address' => 'https://example.test/webhook/primary',
            'is_primary' => true,
            'verified_at' => '2026-07-01T08:00:00+08:00',
            'priority' => 100,
        ],
    ]);

    app()->forgetInstance(FeedbackNotificationRouteResolverContract::class);

    $route = app(FeedbackNotificationRouteResolverContract::class)->resolve(
        new FeedbackRecipientData(type: 'external_endpoint', id: 'endpoint-1'),
        'webhook',
    );

    expect($route)->toBeInstanceOf(FeedbackNotificationRouteData::class)
        ->and($route->address)->toBe('https://example.test/webhook/primary')
        ->and($route->notifiable_type)->toBe('external_endpoint')
        ->and($route->notifiable_id)->toBe('endpoint-1');
});

it('composes notification route resolution into delivery planning without mutating recipients', function () {
    $recipient = feedbackRouteRecipient([
        'sms' => ['address' => '+639171234567', 'verified_at' => '2026-07-01T08:00:00+08:00', 'is_primary' => true],
    ]);

    $intent = new FeedbackIntentData(
        key: 'claim.succeeded',
        message: new FeedbackMessageData(title: 'Claim succeeded', body: 'Your claim succeeded.'),
        recipients: [$recipient],
        channels: [new FeedbackChannelData(key: 'sms')],
    );

    $originalRoutes = $recipient->routes;
    $plan = app(FeedbackDeliveryPlannerContract::class)->plan($intent);

    expect($plan->items)->toHaveCount(1)
        ->and($plan->items[0]->meta['route'])->toBe('+639171234567')
        ->and($plan->items[0]->meta['notification_route'])->toBeArray()
        ->and($plan->items[0]->meta['notification_route']['address'])->toBe('+639171234567')
        ->and($recipient->routes)->toBe($originalRoutes);
});

it('falls back to legacy recipient channel fields while notification routes are adopted', function () {
    $route = app(FeedbackNotificationRouteResolverContract::class)->resolve(
        new FeedbackRecipientData(type: 'claimant', id: 'user-1', email: 'legacy@example.test'),
        'email',
    );

    expect($route)->toBeInstanceOf(FeedbackNotificationRouteData::class)
        ->and($route->address)->toBe('legacy@example.test')
        ->and($route->meta['source'])->toBe('legacy_recipient_field');
});

it('binds the notification route resolver for package consumers', function () {
    expect(app(FeedbackNotificationRouteResolverContract::class))->toBeInstanceOf(FeedbackNotificationRouteResolver::class)
        ->and(app(FeedbackNotificationRouteResolverContract::class))->toBe(app(FeedbackNotificationRouteResolverContract::class));
});

it('keeps notification route baseline independent from persistence contact package routes providers and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Jobs'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(class_exists('LBHurtado\\Contact\\ContactServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackRouteRecipient(array $routes): FeedbackRecipientData
{
    return new FeedbackRecipientData(
        type: 'claimant',
        id: 'user-1',
        name: 'Ana Claimant',
        routes: $routes,
    );
}
