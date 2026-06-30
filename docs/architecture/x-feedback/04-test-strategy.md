# x-feedback — Test Strategy

## Current Coverage

Phase 1 tests cover:

- feedback intent construction
- message actions and artifacts preservation
- recipient/channel/context preservation
- explicit delivery statuses
- dispatcher contract behavior
- channel registry behavior
- unknown channel fail-closed behavior
- default null channel driver
- service provider bindings
- package config defaults
- architecture boundaries
- generic feedback event modeling
- event mapper registration
- event-to-intent mapping
- mapper class-string resolution through the container
- unknown feedback event fail-closed behavior
- event mapping independence from delivery persistence, routes, actions, journal, and lifecycle truth
- feedback template modeling
- key/locale/profile/channel template resolution
- unknown template fail-closed behavior
- placeholder rendering from template and intent variables
- proof that resolving an intent does not mutate the original intent
- template registry and resolver bindings
- template resolution independence from provider delivery, persistence, routes, and host packages
- channel selection policy modeling
- enabled/allowed/disabled/required/preferred/fallback channel selection
- delivery plan item generation per recipient and selected channel
- proof that delivery planning does not resolve provider/channel drivers
- selector and planner package-consumer bindings
- delivery planning independence from provider delivery, persistence, routes, and host packages

## Required Future Coverage

Future phases should add tests for:

- domain event mapping
- template resolution
- channel driver delivery dispatch preparation
- delivery persistence
- retry policy
- expiration/freshness policy
- provider callbacks
- x-action CTA payload rendering
- x-journal receipt handoff
- host integration boundaries

## Invariant Tests

Every phase must prove x-feedback does not own workflow truth, execute actions, mutate voucher state, move money, or become the audit log.
