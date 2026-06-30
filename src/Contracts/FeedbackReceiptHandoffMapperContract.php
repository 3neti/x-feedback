<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

interface FeedbackReceiptHandoffMapperContract
{
    public function fromDelivery(FeedbackDeliveryData $delivery, array $providerPayload = [], ?string $occurredAt = null): FeedbackProviderReceiptData;
}
