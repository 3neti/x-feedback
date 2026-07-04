# x-feedback Parity Report

Date: 2026-07-04

Repository reviewed: `/Users/rli/PhpstormProjects/packages/x-feedback`

Required test command discovered from `composer.json`:

```bash
php -d memory_limit=1G vendor/bin/pest
```

Result: `181 passed (989 assertions)`.

Note: the first test attempt failed because `vendor/bin/pest` did not exist. `composer install` was required. The first install attempt was blocked by sandbox/network/cache permissions; the escalated install completed, after which the package suite passed.

## Stabilization Update — 2026-07-04

Stabilization slice completed for the concrete parity risks identified in this report.

Resolved:

- Durable retry/freshness alignment: `FeedbackRetryFreshnessEvaluator` now prefers canonical durable fields before legacy metadata.
  - Attempts: `record.attempt_count` when greater than zero, then `meta.attempts`, then `0`.
  - Max attempts: `record.max_attempts` when set, then policy max attempts.
  - Last attempt: `record.last_attempted_at`, then `meta.last_attempt_at`.
  - Expiry: `record.expires_at` classifies a non-final record as expired when `now` is after the expiry timestamp.
- Delivery console and operational monitor retry/read-model paths now use the corrected retry evaluator behavior against durable records.
- Provider failure conversion: concrete `email`, `sms`, and `webhook` driver provider exceptions now return `FeedbackDeliveryData` with `failed_retryable`, safe error metadata, and no exception trace.
- Unknown channel behavior remains fail-closed through `UnknownFeedbackChannelException`.
- Webhook payload redaction: webhook sending still uses configured URL, headers, and secret internally, but outbound webhook payload channel options redact secret-like keys recursively, including authorization headers, `secret`, `token`, `api_key`, `client_secret`, `password`, and `signature`.
- Architecture boundary hardening: added tests proving no x-change, x-journal, x-action, x-campaign, Cockpit page/controller/route/job, workflow mutation, action execution, or audit-log ownership has been introduced.

New or updated tests:

- `tests/Feature/FeedbackRetryFreshnessPolicyTest.php`
- `tests/Feature/FeedbackOperationalMonitoringBaselineTest.php`
- `tests/Feature/FeedbackDeliveryConsoleApiBaselineTest.php`
- `tests/Feature/FeedbackTransportDriverBaselineTest.php`
- `tests/Feature/FeedbackArchitectureBoundaryHardeningTest.php`

Verification after stabilization:

```bash
php -d memory_limit=1G vendor/bin/pest tests/Feature/FeedbackRetryFreshnessPolicyTest.php
# 11 passed (46 assertions)

php -d memory_limit=1G vendor/bin/pest tests/Feature/FeedbackOperationalMonitoringBaselineTest.php
# 8 passed (48 assertions)

php -d memory_limit=1G vendor/bin/pest tests/Feature/FeedbackDeliveryConsoleApiBaselineTest.php
# 9 passed (51 assertions)

php -d memory_limit=1G vendor/bin/pest tests/Feature/FeedbackTransportDriverBaselineTest.php
# 15 passed (87 assertions)

php -d memory_limit=1G vendor/bin/pest
# 198 passed (1083 assertions)
```

Formatting command:

```bash
vendor/bin/pint --dirty --format agent
# Failed: vendor/bin/pint is not installed in this package.
```

Remaining risks after stabilization:

- x-feedback is now safer for read-only Cockpit integration through delivery console, operational monitor, and UI component presenter seams.
- It is still not ready for Cockpit-triggered resend/retry mutations until a later explicit action-handoff or retry-execution slice is authorized.
- Queued retry jobs, callback HTTP routes, x-journal persistence, tenant credential database storage, and host authorization/redaction remain intentionally deferred.

## 1. Executive Summary

Overall parity assessment: mostly aligned

Implementation maturity: usable runtime

Primary risk level: moderate

Recommended next action: stabilize

The current package is substantially ahead of the original Phase 1 documents and broadly aligned with the later Compass/evolution-plan direction. It now contains real runtime seams for event mapping, template/profile policy, delivery planning, dispatch preparation, channel drivers, transport sending, durable delivery records, in-app notification state, retry/freshness decisions, provider callback mapping, journal handoff, credentials, monitoring, delivery console read models, and portable UI component data.

The main parity issue is not absence of architecture; it is that the package has moved from a DTO/contract baseline into a usable runtime while some hardening questions remain unresolved. The most concrete implementation risk is a retry/freshness mismatch: durable records store `attempt_count` and `last_attempted_at`, but `FeedbackRetryFreshnessEvaluator` reads `meta['attempts']` and `meta['last_attempt_at']`. This can make retry exhaustion and freshness evaluation inaccurate for records produced by the durable recorder unless callers also provide duplicate metadata.

## 2. Intended Architecture Summary

The intended architecture is a communication infrastructure pipeline:

```text
Domain Event
    ↓
Feedback Mapper
    ↓
Feedback Intent
    ↓
Template / Feature Profile Resolution
    ↓
Feedback Dispatcher
    ↓
Channel Driver
    ↓
Feedback Delivery Record
    ↓
Delivery Receipt / Journal Event
```

x-feedback is intended to own communication intent, rendering preparation, routing, delivery attempts, delivery state, retry/freshness policy, in-app notification primitives, and journal-ready handoff facts. It is not intended to own workflow truth, settlement truth, audit history, campaign orchestration, action execution, artifact storage, provider callback HTTP handling, or Cockpit page ownership.

Package boundaries:

- x-change owns business meaning.
- x-feedback owns communication delivery.
- x-journal owns audit/history.
- x-campaign owns mass distribution.

## 3. As-Built Architecture Summary

Contracts: the package exposes contract seams for channel drivers, driver registry, event mapper registry, template registry/resolution/policy, channel selection, delivery planning, dispatch preparation, dispatcher, attempt runtime, attempt recorder, receipt handoff, journal receipt/event mapping, callback mapping, retry/freshness evaluation, suppression, notification route resolution, credential resolution, webhook sending, operational monitoring, delivery console, in-app state management, action/artifact rendering, and UI component presentation.

Data DTOs: the implementation is DTO-first using `spatie/laravel-data`. DTOs cover intents, recipients, messages, channels, context, events, templates, feature profiles, delivery plans/items, preparations, deliveries, attempts, delivery records, provider receipts/callbacks/responses, journal handoffs, retry policies/decisions/backlog, preferences, quiet hours, suppression decisions, notification routes, credentials, rendered actions/artifacts, monitoring snapshots, console records/history/retry requests, in-app notifications, webhooks, and UI component view models.

Drivers: configured channel keys are `null`, `log`, `in_app`, `email`, `mail`, `sms`, and `webhook`. All implement `FeedbackChannelDriverContract` with `send`, `supports`, and `health`. `null`, `log`, `in_app`, and `mail` are safe baseline/handoff drivers. `email` sends via Laravel Mail. `sms` sends via `LBHurtado\SMS\Facades\SMS`. `webhook` sends through `FeedbackWebhookSenderContract`, whose default implementation wraps Spatie Webhook Server.

Services: the runtime contains deterministic service layers for event mapping, template resolution, template policy, channel selection, route resolution, delivery planning, dispatch preparation, direct dispatch, attempt runtime, durable recording, retry/freshness classification, suppression/preference evaluation, action/artifact rendering, operational monitoring, delivery console read models, in-app notification state transitions, credential lookup, callback normalization, and journal handoff mapping.

Models and migrations: `feedback_delivery_records` stores communication delivery state with delivery/idempotency keys, intent/channel/recipient data, status, attempt count, provider IDs/status/payloads, correlation/causation IDs, terminal timestamps, expiry, metadata, and timestamps. A second migration adds in-app state and read/archive/dismiss timestamps. `FeedbackDeliveryRecord` is a guarded Eloquent model with casts for JSON, integers, and timestamps.

Config: `config/x-feedback.php` declares channel driver bindings, SMS transport defaults, credential/config route/template extension seams, template policy defaults, and action/artifact rendering defaults. Attachments are disabled by default.

Tests: the package has 27 files under `tests/`, including Pest/TestCase support files, 24 feature test files, and one unit data test. The suite exercises most seams and boundary invariants and is green.

Service provider bindings: `XFeedbackServiceProvider` merges config, publishes config, loads migrations, and binds the package contracts to the concrete baseline implementations. The default delivery recorder is now durable database-backed.

## 4. Functional Specification vs As-Built Class Table

| Functional Spec Area | Intended Capability | As-Built Classes / Files | Status | Notes |
|---|---|---|---|---|
| Feedback Intent | Boundary object for communication intent | `FeedbackIntentData`, `FeedbackContextData` | Implemented | Strong alignment; event/business meaning remains upstream. |
| Feedback Recipient | Portable recipient shape | `FeedbackRecipientData` | Implemented | Still includes legacy `email`/`phone`; route model is preferred. |
| Feedback Message | Title/body plus variables/actions/artifacts | `FeedbackMessageData`, `FeedbackTemplateResolver` | Implemented | Template rendering preserves intent immutability in tests. |
| Feedback Actions | Render supplied CTAs without owning them | `FeedbackActionArtifactRenderer`, action DTOs | Implemented | Correctly presentation-only. |
| Feedback Artifacts | Render supplied artifact references by channel policy | `FeedbackActionArtifactRenderer`, artifact DTOs | Implemented | Storage and artifact meaning remain out of scope. |
| Channel Registry | Resolve drivers and fail closed | `FeedbackChannelRegistry`, `UnknownFeedbackChannelException` | Implemented | Covered by tests. |
| Channel Drivers | Channels behind driver contract | `FeedbackChannelDriverContract`, `Drivers/*` | Implemented | Real side effects exist for `email`, `sms`, `webhook`. |
| Template Resolution | Resolve key/locale/profile/channel templates | `FeedbackTemplateRegistry`, `FeedbackTemplateResolver` | Implemented | Missing authoring/versioning UI by design. |
| Feature Profiles | Institutional presentation profile/fallback | `FeedbackFeatureProfileData`, `FeedbackTemplatePolicyResolver` | Implemented | Good separation from locale/business meaning. |
| Delivery Planning | Recipient/channel plan before delivery | `FeedbackChannelSelector`, `FeedbackDeliveryPlanner` | Implemented | Dry planning does not resolve drivers. |
| Dispatch Preparation | Template + plan composition | `FeedbackDispatchPreparer` | Implemented | Side-effect free. |
| Delivery Runtime | Execute prepared plan through drivers | `FeedbackDeliveryAttemptRuntime`, `FeedbackDispatcher` | Implemented | No queue/job runtime. |
| Delivery Records | Durable communication state | migration, `FeedbackDeliveryRecord`, `DatabaseFeedbackDeliveryAttemptRecorder` | Implemented | Durable state exists; not journal truth. |
| Delivery Attempts | Attempt DTO and receipt recording | `FeedbackDeliveryAttemptData`, recorder contract/services | Implemented | Idempotent updates by idempotency key/provider ID/fallback hash. |
| Retry / Freshness | Classify retryable/final/stale/exhausted | `FeedbackRetryFreshnessEvaluator` | Partially Implemented | Evaluator reads retry metadata instead of durable columns; needs hardening. |
| Suppression / Preferences | Advisory non-delivery gates | `FeedbackSuppressionEvaluator`, preference DTOs | Implemented | Side-effect free. |
| Notification Routes | Route resolution separate from entities | `FeedbackNotificationRouteResolver`, route DTO | Implemented | Config-backed and recipient-backed; no database route book. |
| In-App Notifications | Recipient presentation state | in-app migration, `FeedbackInAppNotificationStateManager` | Implemented | Uses delivery records as backing store. |
| Delivery Receipts | Provider receipt handoff facts | `FeedbackProviderReceiptData`, `FeedbackReceiptHandoffMapper` | Implemented | Handoff only. |
| Journal Handoff | Journal-ready communication facts | `FeedbackJournalReceiptMapper`, `FeedbackJournalEventMapper` | Implemented | No x-journal dependency. |
| Provider Callback Mapping | Normalize provider callback facts | `FeedbackProviderCallbackMapper` | Implemented | No routes/signature verification/idempotency handling. |
| Webhook Sending | Send through seam, hide Spatie | `WebhookFeedbackChannelDriver`, `SpatieFeedbackWebhookSender` | Implemented | Spatie is isolated, but dispatch is a real queued side effect. |
| Credential Resolution | Resolve channel/provider/owner credentials | `ConfigFeedbackCredentialResolver`, credential DTOs | Partially Implemented | Config seam only; no tenant store, encryption, rotation. |
| Artifact Rendering Policy | Per-channel preview/link/hide/attach | rendering config, renderer DTOs | Implemented | Attach disabled by default. |
| UI Component Presentation | Portable UI view models, not pages | `FeedbackUiComponentPresenter`, `FeedbackUiComponentData` | Implemented | No Blade/Vue/Inertia assets or Cockpit pages. |
| Operational Monitoring | Read-only channel/failure/retry snapshots | `FeedbackOperationalMonitor` | Implemented | Depends on retry evaluator caveat. |
| Tenant Credentials | Tenant/owner-scoped lookup | `FeedbackCredentialScopeData`, config resolver | Scaffolded | Shape exists, storage/resolution authority deferred. |
| Slack Support | Slack channel delivery | None | Missing | Not implemented in config, drivers, or tests. |
| SMS Support | SMS channel delivery | `SmsFeedbackChannelDriver`, SMS config | Implemented | Uses `lbhurtado/sms`; production credentials/provider behavior not hardened here. |

## 5. As-Built Feature / Benefit Table

| As-Built Feature | Classes / Files | Benefit | Architectural Value | Risk / Caveat |
|---|---|---|---|---|
| DTO-first architecture | `src/Data/*` | Stable portable data grammar | Strong boundary between packages | Many DTOs increase maintenance surface. |
| Driver registry | `FeedbackChannelRegistry` | Configurable channel resolution | Keeps channels pluggable | Unknown channels fail at runtime, not planning. |
| Fail-closed unknown channel behavior | registry/runtime tests | Prevents accidental partial delivery | Preserves safety invariant | Plan can still reference future/unregistered keys. |
| Template registry | `FeedbackTemplateRegistry` | Configured template lookup | Keeps rendering in x-feedback | No persistence/versioning/approval. |
| Feature profile template policy | `FeedbackTemplatePolicyResolver` | Institutional wording/branding fallback | Avoids conflating profile with language | Broad fallback chains could select wrong tone. |
| Delivery planner | `FeedbackDeliveryPlanner` | Produces recipient/channel matrix | Separates planning from sending | Does not validate driver support/capability. |
| Dispatch preparer | `FeedbackDispatchPreparer` | Preflight bundle without side effects | Good composition seam | Not the same as durable queued work. |
| Attempt runtime | `FeedbackDeliveryAttemptRuntime` | Executes prepared plans | Centralizes driver execution | Synchronous, no retry/job orchestration. |
| Durable delivery records | migration, model, database recorder | Observable communication state | Enables console, retry, in-app state | Moderate risk of being mistaken for audit truth. |
| Retry freshness evaluator | `FeedbackRetryFreshnessEvaluator` | Retry classification | Correct ownership of retry decisions | Reads metadata fields that durable recorder does not populate as canonical columns. |
| Suppression evaluator | `FeedbackSuppressionEvaluator` | Opt-out/quiet-hours/freshness gates | Keeps preferences advisory and testable | Not composed into runtime automatically. |
| Notification route resolver | `FeedbackNotificationRouteResolver` | Decouples addresses from recipient entity | Good migration path | Durable route book is absent. |
| Action/artifact renderer | `FeedbackActionArtifactRenderer` | Channel presentation policy | Keeps CTA/artifact meaning upstream | Rendered URLs need host authorization/redaction. |
| Journal receipt mapper | `FeedbackJournalReceiptMapper` | x-journal-ready facts | No direct x-journal dependency | Handoff consumers still need canonical persistence. |
| Provider callback mapper | `FeedbackProviderCallbackMapper` | Normalizes callback facts | Keeps callbacks as communication facts | No HTTP handling/signature/idempotency. |
| Operational monitor | `FeedbackOperationalMonitor` | Read-only health/failure/retry snapshot | Useful package-owned visibility seam | Retry counts inherit evaluator caveat. |
| UI component presenter | `FeedbackUiComponentPresenter` | Portable component view models | Keeps pages host-owned | Component keys may become premature if UI framework requirements differ. |
| Spatie webhook sender seam | `FeedbackWebhookSenderContract`, `SpatieFeedbackWebhookSender` | Avoids Spatie coupling in driver API | Good provider abstraction | Actual dispatch side effect exists. |
| SMS transport seam | `SmsFeedbackChannelDriver` | First concrete SMS path | Aligns with spec SMS support | Depends on provider config and facade behavior. |

## 6. Architecture Invariant Compliance Checklist

| Invariant | Compliant? | Evidence | Concern |
|---|---:|---|---|
| x-feedback does not own business meaning | Yes | Generic event/intents, no x-change classes | Test examples use claim wording but not claim objects. |
| Workflows emit events, not notifications | Mostly | Event mapper registry exists | No host workflow integration is present to prove adoption. |
| FeedbackIntent is the boundary object | Yes | Drivers accept intent/recipient/channel DTOs | None found. |
| Channels are drivers | Yes | All configured channels use `FeedbackChannelDriverContract` | Planning does not require registered drivers. |
| Delivery must be observable | Yes | Durable records, console, monitor | Observability is package-local, not host-integrated. |
| Delivery state is explicit | Yes | `FeedbackDeliveryData::statuses()` and status columns | No enforced DB enum/check constraint. |
| Retry logic belongs to x-feedback | Yes | Retry evaluator and console retry request | Executes no retries by design. |
| Freshness checked before sending | Partially | Suppression evaluator can gate stale intents | Runtime dispatch path does not automatically invoke suppression/freshness. |
| in_app is not audit log | Yes | In-app state separate from delivery status | Shares delivery table, which needs continued discipline. |
| x-journal owns historical narrative | Yes | Journal mappers are handoff-only; no dependency | Handoff names are fixed in x-feedback. |
| x-campaign owns mass distribution | Yes | No campaign classes or orchestration | Bulk in-app mark-read is recipient state, not distribution. |
| CTA ownership is upstream | Yes | Renderer only renders supplied actions | No x-action dependency. |
| Artifact meaning is upstream | Yes | Renderer filters/presents supplied artifacts | No artifact permission model. |
| Artifact storage is not x-feedback responsibility | Yes | No storage package or artifact persistence | URLs/previews can still leak sensitive references. |
| Attachments disabled by default | Yes | Config defaults `allow_attachments` false | Explicit attach policy can enable distribution. |
| Notification routes separate from entities | Mostly | Route DTO/resolver exists | Recipient DTO still carries legacy `email`/`phone`. |
| Voucher is not generally notifiable | Yes | No voucher class dependency | Tests do not explicitly assert voucher absence by name. |
| Credentials resolved, not hardcoded | Mostly | Config credential resolver and scope DTOs | SMS sender default `XCHANGE` and driver default are config/env defaults; secret storage deferred. |
| Provider failures do not break workflows | Partially | Mapping/records keep facts separate | Concrete driver exceptions are not broadly caught/converted. |
| UI components package-owned, pages host-owned | Yes | UI presenter returns view models only | Component model may need host validation. |
| No provider-specific coupling in core services | Mostly | Spatie behind sender; SMS/Mail in drivers | Composer requires provider packages; drivers contain provider facade calls. |
| Delivery attempts idempotent | Partially | Recorder upserts by idempotency/provider/fallback key | Runtime itself does not enforce idempotency before send. |
| Sensitive data minimized | Mostly | Credential and console redaction tests | Webhook payload includes channel options, potentially including secrets. |
| Incrementally adoptable | Yes | Config/bindings and DTO seams | Default durable migration is now loaded automatically. |

## 7. Test Coverage Review

Test files present:

- Unit: `tests/Unit/FeedbackDataTest.php`
- Feature: package bootstrap, dispatch, event mapping, template resolution, delivery planning, dispatch preparation, attempt runtime, recording, durable records, in-app notifications, routes, suppression, feature profile policy, rendering policy, retry/freshness, channel drivers, transport drivers, callback mapping, journal handoff, credentials, operational monitoring, delivery console, and UI component presentation.

Most tests are feature-level package tests through Laravel Testbench. The state machine is reasonably protected at DTO/record/presentation level, but not by a single end-to-end state transition test from real driver send through durable recording through retry/monitoring. Retry/freshness behavior is covered in isolation but not adequately covered against durable database records. Driver behavior is covered with fakes/facades and seam assertions. Boundary invariants are repeatedly asserted by absence of host package dependencies, routes, jobs, action storage, and journal dependencies.

| Test Area | Existing Tests | Coverage Quality | Missing Tests |
|---|---|---|---|
| Core DTO grammar | `FeedbackDataTest` | Good | More invalid-input/serialization edge cases. |
| Service provider/config | `PackageBootstrapTest` | Good | None major. |
| Event mapping | `FeedbackEventMappingTest` | Good | Real host event adapter tests deferred. |
| Template/profile policy | template and feature-profile tests | Good | Versioning/approval intentionally absent. |
| Channel registry/drivers | driver architecture and transport tests | Good | Driver exception/failure conversion. |
| Delivery planning/preparation | planning and preparation tests | Good | Support/capability validation during planning. |
| Delivery runtime | attempt runtime tests | Moderate | End-to-end runtime plus recorder persistence. |
| Durable records | durable/recording tests | Good | Concurrency/upsert race behavior and DB constraints. |
| State machine behavior | status DTO, durable timestamps, in-app tests | Moderate | Invalid transition prevention and unified state-machine matrix. |
| Retry/freshness | retry policy and monitor/console tests | Partial | Durable `attempt_count`/`last_attempted_at` integration. |
| Suppression/preferences | suppression tests | Good | Runtime composition before send. |
| Routes | notification route tests | Good | Durable route store and authorization deferred. |
| Callback mapping | provider callback tests | Moderate | Callback idempotency, signature verification, HTTP route handling deferred. |
| Journal handoff | receipt/event handoff tests | Good | Actual x-journal consumer integration deferred. |
| Credentials | credential tests | Moderate | Secret storage, rotation, tenant package integration deferred. |
| UI components | UI presenter tests | Moderate | Host rendering/accessibility not covered. |
| Boundary invariants | repeated package-boundary tests | Good | Static architecture tests could be consolidated and expanded. |

## 8. Package Boundary Review

x-change: no direct dependency or source coupling was found. The package uses generic event/intent/context data. Risk is mapper implementations can become business-decision code if future host mappers infer claim or settlement state instead of translating upstream facts.

x-journal: no direct dependency was found. The package creates journal-ready receipt/event handoff DTOs only. Risk is delivery records plus journal event names can be mistaken for audit truth unless x-journal remains the canonical narrative.

x-campaign: no campaign orchestration exists. No mass distribution model was found. This boundary is currently clean.

Artifact/media package: x-feedback renders supplied artifact references only. No storage, file generation, or lifecycle ownership exists. Risk remains around exposing artifact URLs/previews without host authorization.

Host app/Cockpit: delivery console and UI components are read-model/view-model seams, not pages, routes, or controllers. This is aligned. Risk is host apps may treat console retry requests as authorization/execution unless the handoff-only contract remains explicit.

Provider SDKs: provider coupling exists only in drivers/sender implementations: Laravel Mail, `lbhurtado/sms`, and Spatie Webhook Server. The core services mostly avoid provider-specific coupling. One sensitive-data risk is webhook payload includes `channel.options`; if options contain secrets, they can be sent in payloads unless hosts avoid placing secrets there.

## 9. Compass Review

`docs/architecture/x-feedback/X_FEEDBACK_COMPASS.md` is broadly accurate for the current source code. It correctly states that Wave 3 baseline work is complete through Phase 23 and identifies the transition/review as the next step.

The Compass is not materially stale, but it needed a new parity-review history entry with this report path, date, findings, risks, and next action. That update has been appended without erasing prior history.

## 10. Recommendations

Immediate recommendations:

- Stabilize retry/freshness against durable delivery records by aligning evaluator inputs with `attempt_count`, `max_attempts`, `last_attempted_at`, and `expires_at`.
- Add tests that create durable records through `DatabaseFeedbackDeliveryAttemptRecorder` and then evaluate retry backlog/console retry eligibility.
- Add failure-conversion tests for concrete email/SMS/webhook drivers so provider exceptions become communication failures where appropriate and do not accidentally break upstream workflows.
- Redact or exclude secret-like values from webhook channel options before placing options into outbound payloads.

Next slice recommendation:

- Run a stabilization slice before adding channels or host integrations. The slice should focus on durable state machine behavior, retry/freshness correctness, provider failure mapping, and sensitive-data minimization.

Stabilization recommendations:

- Add architecture invariant tests for no routes/controllers/jobs unless explicitly authorized.
- Add package-boundary tests for no x-change, x-journal, x-campaign, x-action, or artifact-storage dependencies.
- Verify durable delivery persistence behavior across repeated provider callbacks, missing provider message IDs, and explicit idempotency keys.
- Consider a single end-to-end characterization test: event mapper -> template/profile resolution -> planning -> runtime send through fake driver -> durable record -> retry/monitoring/journal handoff.

Deferred recommendations:

- Avoid adding WhatsApp, Viber, Slack, or other channels until route resolution, credential scoping, provider failure conversion, and durable retry behavior are stable.
- Keep Cockpit pages, HTTP routes, callback signature verification, queue jobs, tenant credential storage, and campaign orchestration deferred until explicitly authorized.
- Do not expand artifact handling into storage, file generation, or permissioning inside x-feedback.
