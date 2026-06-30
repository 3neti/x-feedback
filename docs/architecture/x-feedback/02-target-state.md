# x-feedback — Target State

## Mission

x-feedback is the communication and notification infrastructure layer for the Settlement Operating System.

Target runtime:

```text
Domain Event
    ↓
Feedback Mapper
    ↓
Feedback Intent
    ↓
Template Resolution
    ↓
Feedback Dispatcher
    ↓
Channel Driver
    ↓
Feedback Delivery Record
    ↓
Delivery Receipt / Journal Event
```

## Responsibilities

x-feedback owns:

- feedback intents
- message rendering
- template resolution
- channel routing
- channel drivers
- delivery tracking
- retry policy
- freshness policy
- in-app notification primitives

x-feedback does not own:

- workflow meaning
- claim state
- settlement state
- campaign orchestration
- audit history
- action execution

## Phase 1 Position

Phase 1 establishes only the grammar and channel contract baseline.

Durable delivery records, retry scheduling, templates, queues, provider callbacks, and host integrations remain deferred.
