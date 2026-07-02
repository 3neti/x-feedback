<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackCredentialScopeData extends Data
{
    public function __construct(
        public string $owner_type = 'default',
        public ?string $owner_id = null,
        public ?string $tenant_id = null,
        public ?string $profile = null,
    ) {}

    public function key(): string
    {
        if ($this->owner_type === 'default' || $this->owner_id === null || $this->owner_id === '') {
            return 'default';
        }

        return $this->owner_type.':'.$this->owner_id;
    }
}
