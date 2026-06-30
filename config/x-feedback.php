<?php

use LBHurtado\XFeedback\Drivers\NullFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\InAppFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\LogFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\MailFeedbackChannelDriver;
use LBHurtado\XFeedback\Drivers\WebhookFeedbackChannelDriver;

return [
    'channels' => [
        'null' => NullFeedbackChannelDriver::class,
        'log' => LogFeedbackChannelDriver::class,
        'in_app' => InAppFeedbackChannelDriver::class,
        'mail' => MailFeedbackChannelDriver::class,
        'webhook' => WebhookFeedbackChannelDriver::class,
    ],

    'mappers' => [
        //
    ],

    'templates' => [
        //
    ],
];
