<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackTemplateData;

interface FeedbackTemplateRegistryContract
{
    public function register(FeedbackTemplateData $template): void;

    public function template(
        string $key,
        ?string $locale = null,
        ?string $profile = null,
        ?string $channel = null,
    ): FeedbackTemplateData;
}
