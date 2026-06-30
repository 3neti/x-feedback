<?php

use LBHurtado\XFeedback\Drivers\NullFeedbackChannelDriver;

return [
    'channels' => [
        'null' => NullFeedbackChannelDriver::class,
    ],

    'mappers' => [
        //
    ],
];
