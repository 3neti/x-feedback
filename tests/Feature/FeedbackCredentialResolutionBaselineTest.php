<?php

use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackCredentialData;
use LBHurtado\XFeedback\Data\FeedbackCredentialRequestData;
use LBHurtado\XFeedback\Data\FeedbackCredentialScopeData;
use LBHurtado\XFeedback\Services\ConfigFeedbackCredentialResolver;

it('models credential scope and requests without owning tenant storage', function () {
    $scope = new FeedbackCredentialScopeData(
        owner_type: 'institution',
        owner_id: 'inst-1',
        tenant_id: 'tenant-1',
        profile: 'default',
    );
    $request = new FeedbackCredentialRequestData(
        channel: 'sms',
        provider: 'engagespark',
        purpose: 'delivery',
        scope: $scope,
        context: ['intent_key' => 'claim.succeeded.claimant'],
    );

    expect($request->channel)->toBe('sms')
        ->and($request->provider)->toBe('engagespark')
        ->and($request->scope->owner_type)->toBe('institution')
        ->and($request->context)->toBe(['intent_key' => 'claim.succeeded.claimant'])
        ->and($request->context)->not->toHaveKey('api_key')
        ->and($request->context)->not->toHaveKey('secret');
});

it('resolves owner and provider specific credentials from configuration', function () {
    config()->set('x-feedback.credentials', [
        'sms' => [
            'engagespark' => [
                'institution:inst-1' => [
                    'secrets' => ['api_key' => 'secret-sms-key'],
                    'public' => ['sender' => 'INST1'],
                    'meta' => ['source' => 'vault-reference'],
                ],
            ],
        ],
    ]);

    $credentials = app(FeedbackCredentialResolverContract::class)->resolve(new FeedbackCredentialRequestData(
        channel: 'sms',
        provider: 'engagespark',
        scope: new FeedbackCredentialScopeData(owner_type: 'institution', owner_id: 'inst-1'),
    ));

    expect($credentials)->toBeInstanceOf(FeedbackCredentialData::class)
        ->and($credentials->found)->toBeTrue()
        ->and($credentials->channel)->toBe('sms')
        ->and($credentials->provider)->toBe('engagespark')
        ->and($credentials->scope->owner_type)->toBe('institution')
        ->and($credentials->scope->owner_id)->toBe('inst-1')
        ->and($credentials->secrets)->toBe(['api_key' => 'secret-sms-key'])
        ->and($credentials->public)->toBe(['sender' => 'INST1'])
        ->and($credentials->meta['source'])->toBe('vault-reference');
});

it('falls back from owner credentials to provider defaults without leaking secrets into exposure payloads', function () {
    config()->set('x-feedback.credentials', [
        'webhook' => [
            'default' => [
                'default' => [
                    'secrets' => ['signing_secret' => 'secret-webhook-key'],
                    'public' => ['headers' => ['X-Feedback' => 'enabled']],
                ],
            ],
        ],
    ]);

    $credentials = app(FeedbackCredentialResolverContract::class)->resolve(new FeedbackCredentialRequestData(
        channel: 'webhook',
        provider: 'default',
        scope: new FeedbackCredentialScopeData(owner_type: 'institution', owner_id: 'missing-inst'),
    ));

    expect($credentials->found)->toBeTrue()
        ->and($credentials->scope->owner_type)->toBe('default')
        ->and($credentials->secrets)->toBe(['signing_secret' => 'secret-webhook-key'])
        ->and($credentials->exposure())->toBe([
            'found' => true,
            'channel' => 'webhook',
            'provider' => 'default',
            'scope' => [
                'owner_type' => 'default',
                'owner_id' => null,
                'tenant_id' => null,
                'profile' => null,
            ],
            'public' => ['headers' => ['X-Feedback' => 'enabled']],
            'secret_keys' => ['signing_secret'],
            'meta' => ['redacted' => true],
        ])
        ->and($credentials->exposure())->not->toContain('secret-webhook-key');
});

it('returns an explicit missing credential result when no credential entry matches', function () {
    config()->set('x-feedback.credentials', []);

    $credentials = app(FeedbackCredentialResolverContract::class)->resolve(new FeedbackCredentialRequestData(
        channel: 'email',
        provider: 'smtp',
        scope: new FeedbackCredentialScopeData(owner_type: 'customer', owner_id: 'customer-1'),
    ));

    expect($credentials->found)->toBeFalse()
        ->and($credentials->channel)->toBe('email')
        ->and($credentials->provider)->toBe('smtp')
        ->and($credentials->secrets)->toBe([])
        ->and($credentials->public)->toBe([])
        ->and($credentials->meta['credential_source'])->toBe('missing');
});

it('preserves the legacy credentialsFor channel API as public config only', function () {
    config()->set('x-feedback.credentials', [
        'sms' => [
            'engagespark' => [
                'default' => [
                    'secrets' => ['api_key' => 'secret-sms-key'],
                    'public' => ['sender' => 'XCHANGE'],
                ],
            ],
        ],
    ]);

    $credentials = app(FeedbackCredentialResolverContract::class)->credentialsFor(new FeedbackChannelData(
        key: 'sms',
        options: ['provider' => 'engagespark'],
    ));

    expect($credentials)->toBe(['sender' => 'XCHANGE'])
        ->and($credentials)->not->toHaveKey('api_key');
});

it('does not leak credential secrets into delivery records provider responses or journal handoff payloads', function () {
    config()->set('x-feedback.credentials', [
        'sms' => [
            'engagespark' => [
                'default' => [
                    'secrets' => ['api_key' => 'secret-sms-key'],
                    'public' => ['sender' => 'XCHANGE'],
                ],
            ],
        ],
    ]);

    $credentials = app(FeedbackCredentialResolverContract::class)->resolve(new FeedbackCredentialRequestData(
        channel: 'sms',
        provider: 'engagespark',
    ));

    expect($credentials->exposure())->not->toContain('secret-sms-key')
        ->and($credentials->exposure()['secret_keys'])->toBe(['api_key'])
        ->and($credentials->meta['rendered_message'])->toBeFalse()
        ->and($credentials->meta['delivery_record'])->toBeFalse()
        ->and($credentials->meta['journal_payload'])->toBeFalse();
});

it('binds the config credential resolver for package consumers', function () {
    expect(app(FeedbackCredentialResolverContract::class))->toBeInstanceOf(ConfigFeedbackCredentialResolver::class)
        ->and(app(FeedbackCredentialResolverContract::class))->toBe(app(FeedbackCredentialResolverContract::class));
});

it('keeps credential resolution independent from secret storage tenant packages routes and host packages', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Http'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models/Credential'))->toBeFalse()
        ->and(is_dir($packageRoot.'/database/migrations/credentials'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});
