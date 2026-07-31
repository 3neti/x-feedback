# x-feedback

`x-feedback` provides provider-neutral communication intent, delivery, callback, health, retry-policy, and journal-handoff contracts for the x-change Settlement Operating System.

## Install

```bash
composer require 3neti/x-feedback
```

Laravel discovers `XFeedbackServiceProvider` automatically.

## Runtime boundary

The consuming application owns durable queue orchestration. x-change dispatches feedback work exclusively to its dedicated `x-change-feedback` queue; x-feedback resolves channel drivers and returns sanitized delivery outcomes.

Journal handoffs preserve the lifecycle from `feedback.created` through queued or sent outcomes to `feedback.sent` or `feedback.failed`. Provider SDKs are invoked only inside the selected transport driver, never from campaign or funding code.

## Supported channels

- email and mail handoff;
- SMS through `lbhurtado/sms`;
- webhook delivery;
- in-app and log channels;
- null/test drivers for fail-closed verification.

## Compatibility

- PHP 8.3 or 8.4
- Laravel 12 or 13
- Pest 3 or 4 for package development

## Quality gates

```bash
composer validate --strict
vendor/bin/pint --test
composer test
composer audit --abandoned=report
```

`lbhurtado/engagespark` currently pulls the abandoned `eloquent/enumeration` package transitively. It has no published advisory; the release gate reports it until the upstream transport replaces it.
