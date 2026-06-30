<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackDeliveryPlanItemData extends Data
{
    public const string StatusPlanned = 'planned';

    public function __construct(
        public string $intent_key,
        public FeedbackRecipientData $recipient,
        public string $channel,
        public string $status = self::StatusPlanned,
        public int $priority = 100,
        public ?string $reason = null,
        public ?string $correlation_id = null,
        public ?string $causation_id = null,
        public array $meta = [],
    ) {}
}
