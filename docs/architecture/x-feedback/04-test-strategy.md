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
- dispatch preparation composition of template resolution and delivery planning
- proof that dispatch preparation does not dispatch provider delivery
- provider receipt handoff DTO modeling
- delivery-result to provider-receipt handoff mapping
- dispatch preparation and receipt handoff package-consumer bindings
- dispatch preparation independence from provider delivery, persistence, routes, and host packages
- prepared delivery plan execution through registered channel drivers
- default null-driver delivery attempt execution
- unknown planned channel fail-closed behavior before later side effects
- delivery attempt receipt handoff generation
- delivery attempt runtime package-consumer binding
- delivery attempt runtime independence from persistence, queues, routes, and host packages
- delivery record DTO modeling
- delivery attempt recording through recorder seam
- in-memory delivery lookup by correlation ID and intent key
- recorder reset without mutating attempt data
- non-persistent recorder package-consumer binding
- delivery recording independence from persistence, journal, routes, jobs, and host packages
- journal-ready receipt handoff DTO modeling
- delivery record to journal-ready receipt mapping
- provider receipt to journal-ready receipt mapping
- batch delivery record handoff mapping
- journal receipt mapper package-consumer binding
- journal receipt handoff independence from x-journal dependency, persistence, routes, jobs, and host packages
- provider callback DTO modeling
- provider callback to provider receipt mapping
- provider callback to feedback event mapping
- provider callback status normalization
- provider callback mapper package-consumer binding
- provider callback mapping independence from webhook routes, provider SDKs, persistence, queues, and host packages
- retry/freshness policy DTO modeling
- retryable delivery record classification
- final delivery record classification
- stale delivery record expiration classification
- max-attempt exhaustion classification
- retry/freshness evaluator package-consumer binding
- retry/freshness independence from queues, persistence, provider SDKs, routes, and host packages

## Required Future Coverage

Future phases should add tests for:

- domain event mapping
- template resolution
- channel driver delivery dispatch preparation
- delivery persistence
- expiration/freshness policy
- preference and suppression policy
- provider callback idempotency strategy
- x-action CTA payload rendering
- x-journal receipt handoff
- host integration boundaries

## Invariant Tests

Every phase must prove x-feedback does not own workflow truth, execute actions, mutate voucher state, move money, or become the audit log.
