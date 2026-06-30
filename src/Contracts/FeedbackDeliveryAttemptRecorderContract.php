<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryAttemptData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;

interface FeedbackDeliveryAttemptRecorderContract
{
    /**
     * @return array<int, FeedbackDeliveryRecordData>
     */
    public function record(FeedbackDeliveryAttemptData $attempt): array;

    /**
     * @return array<int, FeedbackDeliveryRecordData>
     */
    public function all(): array;

    /**
     * @return array<int, FeedbackDeliveryRecordData>
     */
    public function forCorrelation(string $correlationId): array;

    /**
     * @return array<int, FeedbackDeliveryRecordData>
     */
    public function forIntent(string $intentKey): array;

    public function reset(): void;
}
