<?php

declare(strict_types=1);

namespace LBHurtado\XFeedback\Services;

final class PaymentSmsContent
{
    public function generic(int $grossAmountMinor): string
    {
        return trans('x-feedback::payment_sms.generic', [
            'amount_paid' => $this->amount($grossAmountMinor),
        ]);
    }

    public function topUp(int $grossAmountMinor): string
    {
        return trans('x-feedback::payment_sms.top_up_received', [
            'amount_paid' => $this->amount($grossAmountMinor),
        ]);
    }

    public function authenticatedIssuance(int $grossAmountMinor): string
    {
        return trans('x-feedback::payment_sms.authenticated_issuance', [
            'amount_paid' => $this->amount($grossAmountMinor),
        ]);
    }

    public function payCodeIssued(int $grossAmountMinor, int $codeValueMinor, string $code, string $claimUrl): string
    {
        return trans('x-feedback::payment_sms.pay_code_issued', [
            'amount_paid' => $this->amount($grossAmountMinor),
            'code_value' => $this->amount($codeValueMinor),
            'code' => $code,
            'claim_url' => $claimUrl,
        ]);
    }

    public function campaign(int $grossAmountMinor, string $completionText): string
    {
        return trans('x-feedback::payment_sms.campaign', [
            'amount_paid' => $this->amount($grossAmountMinor),
            'completion' => $completionText,
        ]);
    }

    private function amount(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2);
    }
}
