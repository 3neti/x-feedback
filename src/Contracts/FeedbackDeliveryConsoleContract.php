<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleHistoryData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRecordData;
use LBHurtado\XFeedback\Data\FeedbackDeliveryConsoleRetryRequestData;
use LBHurtado\XFeedback\Data\FeedbackProviderResponseData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;

interface FeedbackDeliveryConsoleContract
{
    public function status(string $deliveryId): FeedbackDeliveryConsoleRecordData;

    public function history(array $filters = []): FeedbackDeliveryConsoleHistoryData;

    public function providerResponse(string $deliveryId): FeedbackProviderResponseData;

    public function retryRequest(
        string $deliveryId,
        string $requestedBy,
        ?string $reason = null,
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackDeliveryConsoleRetryRequestData;
}
