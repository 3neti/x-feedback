<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackCredentialData;
use LBHurtado\XFeedback\Data\FeedbackCredentialRequestData;

final class NullFeedbackCredentialResolver implements FeedbackCredentialResolverContract
{
    public function resolve(FeedbackCredentialRequestData $request): FeedbackCredentialData
    {
        return new FeedbackCredentialData(
            found: false,
            channel: $request->channel,
            provider: $request->provider,
            scope: $request->scope,
            meta: [
                'credential_source' => 'null',
                'rendered_message' => false,
                'delivery_record' => false,
                'journal_payload' => false,
            ],
        );
    }

    public function credentialsFor(FeedbackChannelData $channel): array
    {
        return [];
    }
}
