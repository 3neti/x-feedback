<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackNotificationRouteResolverContract;
use LBHurtado\XFeedback\Data\FeedbackNotificationRouteData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackNotificationRouteResolver implements FeedbackNotificationRouteResolverContract
{
    public function __construct(
        private readonly array $routes = [],
    ) {}

    public function resolve(FeedbackRecipientData $recipient, string $channel): ?FeedbackNotificationRouteData
    {
        return $this->resolveAll($recipient, $channel)[0] ?? null;
    }

    public function resolveAll(FeedbackRecipientData $recipient, string $channel): array
    {
        $routes = [
            ...$this->routesFromRecipient($recipient, $channel),
            ...$this->routesFromConfig($recipient, $channel),
        ];

        if ($routes === []) {
            $legacyRoute = $this->legacyRoute($recipient, $channel);

            if ($legacyRoute !== null) {
                $routes[] = $legacyRoute;
            }
        }

        usort($routes, fn (FeedbackNotificationRouteData $left, FeedbackNotificationRouteData $right): int => $this->sort($left, $right));

        return $routes;
    }

    /**
     * @return array<int, FeedbackNotificationRouteData>
     */
    private function routesFromRecipient(FeedbackRecipientData $recipient, string $channel): array
    {
        if (! array_key_exists($channel, $recipient->routes)) {
            return [];
        }

        return $this->normalizeRoutes($recipient->routes[$channel], $recipient, $channel, ['source' => 'recipient_routes']);
    }

    /**
     * @return array<int, FeedbackNotificationRouteData>
     */
    private function routesFromConfig(FeedbackRecipientData $recipient, string $channel): array
    {
        $routes = [];

        foreach ($this->routes as $route) {
            if (! is_array($route)) {
                continue;
            }

            $candidate = $this->routeFromArray($route, $recipient, $channel, ['source' => 'config']);

            if ($candidate !== null && $this->matches($candidate, $recipient, $channel)) {
                $routes[] = $candidate;
            }
        }

        return $routes;
    }

    /**
     * @return array<int, FeedbackNotificationRouteData>
     */
    private function normalizeRoutes(mixed $value, FeedbackRecipientData $recipient, string $channel, array $meta): array
    {
        if ($value instanceof FeedbackNotificationRouteData) {
            return [$value];
        }

        if (is_string($value) || is_numeric($value)) {
            return [$this->route((string) $value, $recipient, $channel, $meta)];
        }

        if (! is_array($value)) {
            return [];
        }

        if ($this->isRouteArray($value)) {
            $route = $this->routeFromArray($value, $recipient, $channel, $meta);

            return $route === null ? [] : [$route];
        }

        $routes = [];

        foreach ($value as $route) {
            $routes = [...$routes, ...$this->normalizeRoutes($route, $recipient, $channel, $meta)];
        }

        return $routes;
    }

    private function routeFromArray(array $value, FeedbackRecipientData $recipient, string $channel, array $meta): ?FeedbackNotificationRouteData
    {
        $address = $value['address'] ?? $value['route'] ?? $value['value'] ?? null;

        if (! is_string($address) && ! is_numeric($address)) {
            return null;
        }

        return new FeedbackNotificationRouteData(
            notifiable_type: (string) ($value['notifiable_type'] ?? $recipient->type),
            notifiable_id: $value['notifiable_id'] ?? $recipient->id,
            channel: (string) ($value['channel'] ?? $channel),
            address: (string) $address,
            verified_at: isset($value['verified_at']) && is_string($value['verified_at']) ? $value['verified_at'] : null,
            is_primary: (bool) ($value['is_primary'] ?? false),
            priority: is_numeric($value['priority'] ?? null) ? (int) $value['priority'] : 100,
            meta: [...$meta, ...(array) ($value['meta'] ?? [])],
        );
    }

    private function legacyRoute(FeedbackRecipientData $recipient, string $channel): ?FeedbackNotificationRouteData
    {
        $address = $recipient->routeFor($channel);

        if (! is_string($address) && ! is_numeric($address)) {
            return null;
        }

        return $this->route((string) $address, $recipient, $channel, ['source' => 'legacy_recipient_field']);
    }

    private function route(string $address, FeedbackRecipientData $recipient, string $channel, array $meta): FeedbackNotificationRouteData
    {
        return new FeedbackNotificationRouteData(
            notifiable_type: $recipient->type,
            notifiable_id: $recipient->id,
            channel: $channel,
            address: $address,
            meta: $meta,
        );
    }

    private function matches(FeedbackNotificationRouteData $route, FeedbackRecipientData $recipient, string $channel): bool
    {
        if ($route->channel !== $channel) {
            return false;
        }

        if ($route->notifiable_type !== $recipient->type) {
            return false;
        }

        if ((string) $route->notifiable_id !== (string) $recipient->id) {
            return false;
        }

        return true;
    }

    private function isRouteArray(array $value): bool
    {
        return array_key_exists('address', $value)
            || array_key_exists('route', $value)
            || array_key_exists('value', $value);
    }

    private function sort(FeedbackNotificationRouteData $left, FeedbackNotificationRouteData $right): int
    {
        return [
            $left->is_primary ? 0 : 1,
            $left->verified_at !== null ? 0 : 1,
            $left->priority,
            $left->address,
        ] <=> [
            $right->is_primary ? 0 : 1,
            $right->verified_at !== null ? 0 : 1,
            $right->priority,
            $right->address,
        ];
    }
}
