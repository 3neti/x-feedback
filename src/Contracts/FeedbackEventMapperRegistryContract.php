<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

interface FeedbackEventMapperRegistryContract
{
    public function register(string $eventType, string|FeedbackEventMapperContract $mapper): void;

    public function mapper(string $eventType): FeedbackEventMapperContract;

    public function map(FeedbackEventData $event): FeedbackIntentData;
}
