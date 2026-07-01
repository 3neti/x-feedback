<?php

namespace LBHurtado\XFeedback\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailServiceProvider;
use LBHurtado\SMS\SMSServiceProvider;
use LBHurtado\XFeedback\XFeedbackServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\WebhookServer\WebhookServerServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

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

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }
}
