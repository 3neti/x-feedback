<?php

declare(strict_types=1);

namespace LBHurtado\XFeedback\Services;

use Illuminate\Support\Facades\DB;

final class PaymentSmsDailyBudget
{
    public function reserveAttempt(): bool
    {
        $date = now('Asia/Manila')->toDateString();
        $limit = (int) config('x-feedback.payment_sms.daily_attempt_cap', 100);

        if ($limit < 1) {
            return false;
        }

        return DB::transaction(function () use ($date, $limit): bool {
            DB::table('feedback_payment_sms_daily_budgets')->insertOrIgnore([
                'business_date' => $date,
                'attempt_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('feedback_payment_sms_daily_budgets')
                ->where('business_date', $date)
                ->where('attempt_count', '<', $limit)
                ->update([
                    'attempt_count' => DB::raw('attempt_count + 1'),
                    'updated_at' => now(),
                ]) === 1;
        }, attempts: 5);
    }
}
