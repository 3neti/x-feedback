# x-feedback — Architecture Invariants

## x-feedback Does Not Own Business Meaning

x-feedback must not decide what happened or what should happen next.

Upstream packages own workflow meaning.

## Feedback Intent Is the Boundary Object

Drivers receive `FeedbackIntentData`, recipients, and channel data.

Drivers must not receive raw voucher, claim, campaign, or settlement objects as their primary input.

## Channels Are Drivers

Every delivery channel must be represented through `FeedbackChannelDriverContract`.

Channel-specific logic must not leak into workflows.

## Delivery State Is Explicit

Delivery state must use explicit status values such as:

- `pending`
- `queued`
- `sending`
- `sent`
- `delivered`
- `failed_retryable`
- `retry_scheduled`
- `failed_final`
- `expired`
- `cancelled`

Avoid boolean-only delivery state.

## Phase 1 Is Non-Persistent

Phase 1 must not introduce:

- delivery tables
- models
- routes
- controllers
- queues
- provider SDKs

## Communication Is Not Execution

x-feedback must not:

- approve claims
- mutate vouchers
- execute actions
- move money
- decide settlement readiness
- become journal truth
