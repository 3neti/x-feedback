<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackWebhookSendResultData extends Data
{
    public function __construct(
        public string $message_id,
        public string $status = FeedbackDeliveryData::StatusQueued,
        public array $result = [],
    ) {}
}
