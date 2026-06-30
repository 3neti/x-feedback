<?php

use LBHurtado\XFeedback\Drivers\NullFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\EmailFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\InAppFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\LogFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\MailFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\SmsFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\WebhookFeedbackChannelDriver;

return [
    'channels' => [
        'null' => NullFeedbackChannelDriver::class,
        'log' => LogFeedbackChannelDriver::class,
        'in_app' => InAppFeedbackChannelDriver::class,
        'email' => EmailFeedbackChannelDriver::class,
        'mail' => MailFeedbackChannelDriver::class,
        'sms' => SmsFeedbackChannelDriver::class,
        'webhook' => WebhookFeedbackChannelDriver::class,
    ],

    'transports' => [
        'sms' => [
            'driver' => env('X_FEEDBACK_SMS_DRIVER', 'engagespark'),
            'sender' => env('X_FEEDBACK_SMS_SENDER', 'XCHANGE'),
        ],
    ],

    'mappers' => [
        //
    ],

    'templates' => [
        //
    ],
];
