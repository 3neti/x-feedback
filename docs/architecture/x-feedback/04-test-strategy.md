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

## Required Future Coverage

Future phases should add tests for:

- domain event mapping
- template resolution
- channel driver selection
- delivery persistence
- retry policy
- expiration/freshness policy
- provider callbacks
- x-action CTA payload rendering
- x-journal receipt handoff
- host integration boundaries

## Invariant Tests

Every phase must prove x-feedback does not own workflow truth, execute actions, mutate voucher state, move money, or become the audit log.
