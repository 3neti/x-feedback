<?php

declare(strict_types=1);

namespace LBHurtado\XFeedback\Services;

final class PaymentSmsContent
{
    public function generic(int $grossAmountMinor): string
    {
        return str_replace(
            ':amount',
            number_format($grossAmountMinor / 100, 2),
            (string) config('x-feedback.payment_sms.templates.generic', 'You paid ₱:amount. Thank you.'),
        );
    }

    public function campaign(int $grossAmountMinor, string $completionText): string
    {
        return str_replace(
            [':amount', ':completion'],
            [number_format($grossAmountMinor / 100, 2), $completionText],
            (string) config('x-feedback.payment_sms.templates.campaign', 'You paid ₱:amount. :completion'),
        );
    }
}
