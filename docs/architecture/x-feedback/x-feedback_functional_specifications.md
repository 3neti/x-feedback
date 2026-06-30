# x-feedback Functional Specifications

## Purpose

x-feedback centralizes feedback, notification, and communication infrastructure for the x-change ecosystem.

It converts communication intent into channel delivery attempts.

## Phase 1 Functional Baseline

The package supports:

- construction of feedback intents
- construction of feedback messages
- construction of recipients and channels
- explicit delivery status modeling
- registration and resolution of channel drivers
- dispatch through channel drivers
- a null driver for safe tests and baseline integrations

## Out of Scope for Phase 1

- real email/SMS/webhook delivery
- persistent delivery records
- retry scheduling
- provider callbacks
- workflow event mappers
- x-action rendering
- x-journal receipts
- host application wiring
