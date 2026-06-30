<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackCredentialResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelData;

final class NullFeedbackCredentialResolver implements FeedbackCredentialResolverContract
{
    public function credentialsFor(FeedbackChannelData $channel): array
    {
        return [];
    }
}
