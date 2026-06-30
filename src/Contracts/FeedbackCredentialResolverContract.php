<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackChannelData;

interface FeedbackCredentialResolverContract
{
    public function credentialsFor(FeedbackChannelData $channel): array;
}
