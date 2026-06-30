<?php

namespace LBHurtado\XFeedback\Exceptions;

use RuntimeException;

final class UnknownFeedbackChannelException extends RuntimeException
{
    public static function forChannel(string $channel): self
    {
        return new self("Unknown feedback channel [{$channel}].");
    }
}
