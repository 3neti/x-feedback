<?php

namespace LBHurtado\XFeedback\Exceptions;

use RuntimeException;

final class UnknownFeedbackDeliveryRecordException extends RuntimeException
{
    public static function forDeliveryId(string $deliveryId): self
    {
        return new self(sprintf('Unknown feedback delivery record [%s].', $deliveryId));
    }
}
