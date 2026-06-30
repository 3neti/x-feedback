<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Contracts\Container\Container;
use LBHurtado\XFeedback\Contracts\FeedbackChannelDriverContract;
use LBHurtado\XFeedback\Contracts\FeedbackChannelRegistryContract;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackChannelException;

final class FeedbackChannelRegistry implements FeedbackChannelRegistryContract
{
    /**
     * @param  array<string, string|FeedbackChannelDriverContract>  $drivers
     */
    public function __construct(
        private readonly Container $container,
        private array $drivers = [],
    ) {}

    public function register(string $channel, string|FeedbackChannelDriverContract $driver): void
    {
        $this->drivers[$channel] = $driver;
    }

    public function driver(string $channel): FeedbackChannelDriverContract
    {
        $driver = $this->drivers[$channel] ?? null;

        if ($driver === null) {
            throw UnknownFeedbackChannelException::forChannel($channel);
        }

        if (is_string($driver)) {
            $driver = $this->container->make($driver);
        }

        if (! $driver instanceof FeedbackChannelDriverContract) {
            throw UnknownFeedbackChannelException::forChannel($channel);
        }

        return $driver;
    }
}
