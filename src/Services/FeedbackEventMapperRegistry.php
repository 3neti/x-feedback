<?php

namespace LBHurtado\XFeedback\Services;

use Illuminate\Contracts\Container\Container;
use LBHurtado\XFeedback\Contracts\FeedbackEventMapperContract;
use LBHurtado\XFeedback\Contracts\FeedbackEventMapperRegistryContract;
use LBHurtado\XFeedback\Data\FeedbackEventData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackEventMapperException;

final class FeedbackEventMapperRegistry implements FeedbackEventMapperRegistryContract
{
    /**
     * @param  array<string, string|FeedbackEventMapperContract>  $mappers
     */
    public function __construct(
        private readonly Container $container,
        private array $mappers = [],
    ) {}

    public function register(string $eventType, string|FeedbackEventMapperContract $mapper): void
    {
        $this->mappers[$eventType] = $mapper;
    }

    public function mapper(string $eventType): FeedbackEventMapperContract
    {
        $mapper = $this->mappers[$eventType] ?? null;

        if ($mapper === null) {
            throw UnknownFeedbackEventMapperException::forEvent($eventType);
        }

        if (is_string($mapper)) {
            $mapper = $this->container->make($mapper);
        }

        if (! $mapper instanceof FeedbackEventMapperContract) {
            throw UnknownFeedbackEventMapperException::forEvent($eventType);
        }

        return $mapper;
    }

    public function map(FeedbackEventData $event): FeedbackIntentData
    {
        return $this->mapper($event->type)->map($event);
    }
}
