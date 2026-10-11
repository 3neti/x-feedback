<?php

use Illuminate\Support\Carbon;
use LBHurtado\XFeedback\Services\PaymentSmsContent;
use LBHurtado\XFeedback\Services\PaymentSmsDailyBudget;

it('caps payment SMS provider attempts for each Manila business day', function () {
    config()->set('x-feedback.payment_sms.daily_attempt_cap', 2);
    $budget = app(PaymentSmsDailyBudget::class);

    expect($budget->reserveAttempt())->toBeTrue()
        ->and($budget->reserveAttempt())->toBeTrue()
        ->and($budget->reserveAttempt())->toBeFalse();

    $this->assertDatabaseHas('feedback_payment_sms_daily_budgets', [
        'business_date' => now('Asia/Manila')->toDateString(),
        'attempt_count' => 2,
    ]);
});

it('renders one payment message with the gross amount and campaign link', function () {
    $content = app(PaymentSmsContent::class);

    expect($content->generic(5000))->toBe('You paid ₱50.00. Thank you.')
        ->and($content->campaign(5000, 'Finish here: https://example.test/claim'))
        ->toBe('You paid ₱50.00. Finish here: https://example.test/claim');
});

it('starts a new allowance at the Manila day boundary', function () {
    config()->set('x-feedback.payment_sms.daily_attempt_cap', 1);
    Carbon::setTestNow('2026-10-11 15:59:00 UTC');
    $budget = app(PaymentSmsDailyBudget::class);

    expect($budget->reserveAttempt())->toBeTrue()
        ->and($budget->reserveAttempt())->toBeFalse();

    Carbon::setTestNow('2026-10-11 16:01:00 UTC');

    expect($budget->reserveAttempt())->toBeTrue()
        ->and($budget->reserveAttempt())->toBeFalse();

    Carbon::setTestNow();
});
