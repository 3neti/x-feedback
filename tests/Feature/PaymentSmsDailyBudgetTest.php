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
        ->and($content->topUp(5000))->toBe('Top-up payment of ₱50.00 received.')
        ->and($content->payCodeIssued(5201, 4500, 'ABC123', 'https://example.test/claim/ABC123'))
        ->toBe('Paid ₱52.01. ₱45.00 Pay Code ABC123 is ready: https://example.test/claim/ABC123')
        ->and($content->campaign(5000, 'Finish here: https://example.test/claim'))
        ->toBe('You paid ₱50.00. Finish here: https://example.test/claim');
});

it('allows the host to override payment SMS language without changing package files', function () {
    $overridePath = lang_path('vendor/x-feedback/en/payment_sms.php');
    if (! is_dir(dirname($overridePath))) {
        mkdir(dirname($overridePath), 0777, true);
    }
    file_put_contents($overridePath, "<?php return ['top_up_received' => 'Received PHP :amount_paid.'];");

    try {
        expect(app(PaymentSmsContent::class)->topUp(5000))->toBe('Received PHP 50.00.');
    } finally {
        unlink($overridePath);
    }
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
