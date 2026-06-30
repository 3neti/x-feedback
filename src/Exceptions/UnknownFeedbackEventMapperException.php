<?php

namespace LBHurtado\XFeedback\Exceptions;

use RuntimeException;

final class UnknownFeedbackEventMapperException extends RuntimeException
{
    public static function forEvent(string $eventType): self
    {
        return new self("Unknown feedback event mapper for [{$eventType}].");
    }
}
