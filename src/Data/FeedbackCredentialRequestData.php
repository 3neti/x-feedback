<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackCredentialRequestData extends Data
{
    public function __construct(
        public string $channel,
        public ?string $provider = null,
        public string $purpose = 'delivery',
        public ?FeedbackCredentialScopeData $scope = null,
        public array $context = [],
    ) {
        $this->scope ??= new FeedbackCredentialScopeData;
    }
}
