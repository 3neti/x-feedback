# x-feedback — Current State

## Status

`3neti/x-feedback` has been created as a new independent package at:

```text
/Users/rli/PhpstormProjects/packages/x-feedback
```

Before this scaffold, x-feedback did not exist as a package.

Feedback and notification behavior is expected to exist in host packages as workflow side effects. Phase 1 does not migrate those call sites.

## Phase 1 Baseline

The package now contains the first communication grammar:

- feedback intent data
- feedback message data
- feedback recipient data
- feedback channel data
- feedback delivery data
- feedback context data
- channel driver contract
- dispatcher contract
- template resolver contract
- credential resolver contract
- channel registry contract
- null channel driver
- dispatcher service

## Boundary

x-feedback currently does not provide:

- persistence
- delivery record database tables
- routes
- controllers
- UI
- queues
- retries
- provider integrations
- host package integrations

It is a contract and DTO baseline only.
