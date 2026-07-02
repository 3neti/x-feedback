<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackCredentialData;
use LBHurtado\XFeedback\Data\FeedbackCredentialRequestData;

interface FeedbackCredentialResolverContract
{
    public function resolve(FeedbackCredentialRequestData $request): FeedbackCredentialData;

    public function credentialsFor(FeedbackChannelData $channel): array;
}
