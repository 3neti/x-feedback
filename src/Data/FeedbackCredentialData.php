<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackCredentialData extends Data
{
    public function __construct(
        public bool $found,
        public string $channel,
        public ?string $provider = null,
        public ?FeedbackCredentialScopeData $scope = null,
        public array $secrets = [],
        public array $public = [],
        public array $meta = [],
    ) {
        $this->scope ??= new FeedbackCredentialScopeData;
    }

    public function exposure(): array
    {
        return [
            'found' => $this->found,
            'channel' => $this->channel,
            'provider' => $this->provider,
            'scope' => [
                'owner_type' => $this->scope->owner_type,
                'owner_id' => $this->scope->owner_id,
                'tenant_id' => $this->scope->tenant_id,
                'profile' => $this->scope->profile,
            ],
            'public' => $this->public,
            'secret_keys' => array_values(array_keys($this->secrets)),
            'meta' => ['redacted' => true],
        ];
    }
}
