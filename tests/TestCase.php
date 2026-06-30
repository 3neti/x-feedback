<?php

namespace LBHurtado\XFeedback\Tests;

use LBHurtado\XFeedback\XFeedbackServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            XFeedbackServiceProvider::class,
        ];
    }
}
