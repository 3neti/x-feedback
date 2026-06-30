<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackRecipientData extends Data
{
    public function __construct(
        public string $type,
        public string|int|null $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public array $routes = [],
        public array $meta = [],
    ) {}

    public function routeFor(string $channel): mixed
    {
        return $this->routes[$channel] ?? match ($channel) {
            'mail', 'email' => $this->email,
            'sms', 'mobile', 'phone' => $this->phone,
            default => null,
        };
    }
}
