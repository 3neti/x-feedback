<?php

namespace LBHurtado\XFeedback\Tests;

use Illuminate\Mail\MailServiceProvider;
use LBHurtado\SMS\SMSServiceProvider;
use LBHurtado\XFeedback\XFeedbackServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\WebhookServer\WebhookServerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            MailServiceProvider::class,
            SMSServiceProvider::class,
            WebhookServerServiceProvider::class,
            XFeedbackServiceProvider::class,
        ];
    }
}
