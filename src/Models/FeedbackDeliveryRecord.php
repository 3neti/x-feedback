<?php

namespace LBHurtado\XFeedback\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackDeliveryRecord extends Model
{
    protected $table = 'feedback_delivery_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recipient' => 'array',
            'provider_response' => 'array',
            'meta' => 'array',
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }
}
