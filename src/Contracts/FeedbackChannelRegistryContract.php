<?php

namespace LBHurtado\XFeedback\Contracts;

interface FeedbackChannelRegistryContract
{
    public function register(string $channel, string|FeedbackChannelDriverContract $driver): void;

    public function driver(string $channel): FeedbackChannelDriverContract;
}
