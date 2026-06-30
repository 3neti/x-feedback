<?php

namespace LBHurtado\XFeedback\Exceptions;

use RuntimeException;

final class UnknownFeedbackTemplateException extends RuntimeException
{
    public static function forTemplate(string $key): self
    {
        return new self("Unknown feedback template [{$key}].");
    }
}
