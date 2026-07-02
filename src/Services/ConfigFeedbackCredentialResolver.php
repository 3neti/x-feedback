<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackCredentialData;
use LBHurtado\XFeedback\Data\FeedbackCredentialRequestData;
use LBHurtado\XFeedback\Data\FeedbackCredentialScopeData;

final class ConfigFeedbackCredentialResolver implements FeedbackCredentialResolverContract
{
    /**
     * @param  array<string, mixed>|null  $credentials
     */
    public function __construct(
        private ?array $credentials = null,
    ) {}

    public function resolve(FeedbackCredentialRequestData $request): FeedbackCredentialData
    {
        $provider = $request->provider ?? 'default';
        $entries = $this->entries($request->channel, $provider);
        $scope = $request->scope ?? new FeedbackCredentialScopeData;
        $key = $scope->key();

        if (array_key_exists($key, $entries) && is_array($entries[$key])) {
            return $this->credential($request->channel, $provider, $scope, $entries[$key]);
        }

        if (array_key_exists('default', $entries) && is_array($entries['default'])) {
            return $this->credential($request->channel, $provider, new FeedbackCredentialScopeData, $entries['default']);
        }

        return new FeedbackCredentialData(
            found: false,
            channel: $request->channel,
            provider: $provider,
            scope: $scope,
            meta: [
                'credential_source' => 'missing',
                'rendered_message' => false,
                'delivery_record' => false,
                'journal_payload' => false,
            ],
        );
    }

    public function credentialsFor(FeedbackChannelData $channel): array
    {
        $credentials = $this->resolve(new FeedbackCredentialRequestData(
            channel: $channel->key,
            provider: is_scalar($channel->options['provider'] ?? null)
                ? (string) $channel->options['provider']
                : 'default',
        ));

        return $credentials->public;
    }

    /**
     * @return array<string, mixed>
     */
    private function entries(string $channel, string $provider): array
    {
        $credentials = $this->credentials ?? (array) config('x-feedback.credentials', []);
        $channelEntries = (array) ($credentials[$channel] ?? []);

        return (array) ($channelEntries[$provider] ?? $channelEntries['default'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function credential(
        string $channel,
        string $provider,
        FeedbackCredentialScopeData $scope,
        array $entry,
    ): FeedbackCredentialData {
        return new FeedbackCredentialData(
            found: true,
            channel: $channel,
            provider: $provider,
            scope: $scope,
            secrets: (array) ($entry['secrets'] ?? []),
            public: (array) ($entry['public'] ?? []),
            meta: [
                ...(array) ($entry['meta'] ?? []),
                'credential_source' => 'config',
                'rendered_message' => false,
                'delivery_record' => false,
                'journal_payload' => false,
            ],
        );
    }
}
