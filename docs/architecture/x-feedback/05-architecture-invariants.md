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

Early feedback phases must not introduce:

- delivery tables
- models
- routes
- controllers
- queues
- provider SDKs

## Event Mapping Does Not Decide Truth

Feedback event mappers translate upstream facts into communication intents.

They must not:

- decide whether a claim succeeded
- decide whether a payment settled
- inspect voucher internals to infer lifecycle state
- mutate workflow state
- dispatch deliveries directly

If an event is not registered, mapping must fail closed before delivery dispatch.

## Template Resolution Is Rendering Preparation

Template resolution prepares message content.

It must not:

- send provider messages
- persist delivery records
- decide recipients
- decide lifecycle truth
- execute actions
- mutate the original feedback intent

Unknown templates must fail closed before provider delivery.

## Delivery Planning Is Not Delivery

Delivery planning prepares the intended recipient/channel matrix.

It must not:

- invoke channel drivers
- call provider SDKs
- persist delivery records
- queue jobs
- decide lifecycle truth
- prove that a notification was sent

Channel driver resolution remains a dispatch concern.

## Communication Is Not Execution

x-feedback must not:

- approve claims
- mutate vouchers
- execute actions
- move money
- decide settlement readiness
- become journal truth
