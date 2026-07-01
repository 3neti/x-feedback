# x-feedback — Evolution Plan

## Phase 1 — Core Feedback Grammar and Channel Contract Baseline

Status: Complete.

Implemented:

- `FeedbackIntentData`
- `FeedbackRecipientData`
- `FeedbackChannelData`
- `FeedbackMessageData`
- `FeedbackContextData`
- `FeedbackDeliveryData`
- `FeedbackChannelDriverContract`
- `FeedbackChannelRegistryContract`
- `FeedbackDispatcherContract`
- `FeedbackTemplateResolverContract`
- `FeedbackCredentialResolverContract`
- `NullFeedbackChannelDriver`
- `FeedbackChannelRegistry`
- `FeedbackDispatcher`
- `PassthroughFeedbackTemplateResolver`
- `NullFeedbackCredentialResolver`
- package config and service provider bindings

Deferred:

- event mappers
- real channel drivers
- database delivery records
- queues
- retries
- freshness checks
- templates
- x-action integration
- x-journal integration
- x-change integration
- Cockpit visibility

## Phase 2 — Feedback Event Mapping Baseline

Status: Complete.

Implemented:

- `FeedbackEventData`
- `FeedbackEventMapperContract`
- `FeedbackEventMapperRegistryContract`
- `FeedbackEventMapperRegistry`
- `UnknownFeedbackEventMapperException`
- package config mapper extension seam
- event-to-intent mapping tests
- class-string mapper resolution through the container
- fail-closed unknown event behavior

Deferred:

- no real provider delivery
- real host event mappers
- database delivery records
- queues
- retries
- templates
- x-action integration
- x-journal integration

## Phase 3 — Template Resolution Baseline

Status: Complete.

Implemented:

- `FeedbackTemplateData`
- `FeedbackTemplateRegistryContract`
- `FeedbackTemplateRegistry`
- `FeedbackTemplateResolver`
- `UnknownFeedbackTemplateException`
- package config template extension seam
- key/locale/profile/channel template resolution
- safe fallback to key-level default templates
- placeholder rendering from template and intent variables
- resolver binding through `FeedbackTemplateResolverContract`

Deferred:

- no real channel/provider delivery
- no persistence
- template persistence
- template authoring UI
- template versioning
- template approval workflow
- provider-specific rendering

## Phase 4 — Channel Driver Selection and Delivery Planning Baseline

Status: Complete.

Implemented:

- `FeedbackChannelSelectionPolicyData`
- `FeedbackDeliveryPlanData`
- `FeedbackDeliveryPlanItemData`
- `FeedbackChannelSelectorContract`
- `FeedbackDeliveryPlannerContract`
- `FeedbackChannelSelector`
- `FeedbackDeliveryPlanner`
- selector and planner service-provider bindings
- enabled-channel filtering
- allowed-channel filtering
- disabled-channel filtering
- required/preferred/fallback ordering
- recipient/channel delivery plan generation
- dry planning without channel driver resolution

Deferred:

- no real provider delivery
- no persistence
- no queues
- no retry policy
- no provider capability validation
- no x-journal delivery receipt integration
- no host package integration

## Phase 5 — Delivery Dispatch Preparation and Receipt Handoff Baseline

Status: Complete.

Implemented:

- `FeedbackDispatchPreparationData`
- `FeedbackProviderReceiptData`
- `FeedbackDispatchPreparerContract`
- `FeedbackReceiptHandoffMapperContract`
- `FeedbackDispatchPreparer`
- `FeedbackReceiptHandoffMapper`
- dispatch preparation service-provider binding
- receipt handoff mapper service-provider binding
- template resolution plus delivery planning composition
- delivery-result to provider-receipt handoff mapping
- dry dispatch preparation without provider delivery

Deferred:

- no real provider delivery
- no persistence
- no queues
- no retry policy
- no provider SDKs
- no delivery receipt storage
- no x-journal delivery receipt integration
- no host package integration

## Phase 6 — Delivery Attempt Runtime Baseline

Status: Complete.

Implemented:

- `FeedbackDeliveryAttemptData`
- `FeedbackDeliveryAttemptRuntimeContract`
- `FeedbackDeliveryAttemptRuntime`
- delivery attempt runtime service-provider binding
- execution of prepared delivery plan items through registered channel drivers
- provider receipt handoff generation from delivery results
- fail-closed unknown planned channel behavior before later plan items execute
- default null-driver delivery attempt coverage

Deferred:

- no durable delivery records
- no queues
- no retry policy
- no provider SDKs
- no routes
- no host package integration
- no x-journal delivery receipt integration

## Phase 7 — Delivery Recording Strategy Baseline

Status: Complete.

Implemented:

- `FeedbackDeliveryRecordData`
- `FeedbackDeliveryAttemptRecorderContract`
- `InMemoryFeedbackDeliveryAttemptRecorder`
- delivery recorder service-provider binding
- non-canonical delivery record modeling
- in-memory delivery attempt recording from receipt handoff payloads
- lookup by correlation ID and intent key
- recorder reset for tests and short-lived baselines

Deferred:

- no database persistence
- no x-journal dependency
- no routes
- no jobs
- no retry policy
- no provider SDKs
- no host package integration

## Phase 8 — Journal Receipt Handoff Baseline

Status: Complete.

Implemented:

- `FeedbackJournalReceiptData`
- `FeedbackJournalReceiptMapperContract`
- `FeedbackJournalReceiptMapper`
- journal receipt mapper service-provider binding
- delivery record to journal-ready receipt fact mapping
- provider receipt to journal-ready receipt fact mapping
- batch delivery record mapping
- x-journal-ready handoff payloads without x-journal dependency

Deferred:

- no x-journal package dependency
- no database persistence
- no journal persistence
- no routes
- no jobs
- no provider SDKs
- no host package integration

## Phase 9 — Provider Callback Feedback Mapping Baseline

Status: Complete.

Implemented:

- `FeedbackProviderCallbackData`
- `FeedbackProviderCallbackMapperContract`
- `FeedbackProviderCallbackMapper`
- provider callback mapper service-provider binding
- provider callback to provider receipt mapping
- provider callback to feedback event mapping
- provider callback status normalization
- callback mapping without webhook routes or provider SDKs

Deferred:

- no webhook routes
- no provider SDKs
- no database persistence
- no queues
- no retry policy
- no callback idempotency
- no host package integration

## Phase 10 — Retry and Freshness Policy Baseline

Status: Complete.

Implemented:

- `FeedbackRetryPolicyData`
- `FeedbackRetryDecisionData`
- `FeedbackRetryFreshnessEvaluatorContract`
- `FeedbackRetryFreshnessEvaluator`
- retry/freshness evaluator service-provider binding
- retryable delivery record classification
- final delivery record classification
- stale delivery record expiration classification
- max-attempt exhaustion classification
- next retry timestamp calculation from backoff policy

Deferred:

- no queued retries
- no database persistence
- no retry jobs
- no provider SDKs
- no routes
- no host package integration

## Phase 11 — Channel Driver Architecture Backfill

Status: Complete.

Implemented:

- reconciled the current package roadmap with `/Users/rli/PhpstormProjects/x-change-sandbox/docs/todo/x-feedback/03-evolution-plan.md`
- expanded `FeedbackChannelDriverContract` to support:
  - `send`
  - `supports`
  - `health`
- added `FeedbackChannelHealthData`
- added safe baseline channel drivers:
  - `NullFeedbackChannelDriver`
  - `LogFeedbackChannelDriver`
  - `InAppFeedbackChannelDriver`
  - `MailFeedbackChannelDriver`
  - `WebhookFeedbackChannelDriver`
- registered baseline drivers in package configuration
- added driver registry resolution tests for known drivers
- preserved unknown-driver fail-closed behavior
- added driver health tests
- added driver supports/capability tests
- added safe handoff delivery tests proving no provider side effects

Reason:

The original planning docs define Phase 2 as Channel Driver Architecture and list `mail`, `webhook`, `in_app`, `log`, and `null` as initial drivers. Phase 11 backfilled this safe baseline before continuing to higher-level preference and suppression policy.

Deferred:

- no provider SDKs
- no real HTTP webhook calls
- no SMTP assumptions
- no queues
- no routes
- no durable persistence
- no host package integration

## Phase 12 — Transport Driver Baseline

Status: Complete.

Implemented:

- created first-class transport drivers:
  - `email`
  - `sms`
  - `webhook`
- added `FeedbackEmailMessage`
- added `EmailFeedbackChannelDriver` using Laravel Mail
- added `SmsFeedbackChannelDriver` using `lbhurtado/sms`
- added `FeedbackWebhookMessageData`
- added `FeedbackWebhookSendResultData`
- added `FeedbackWebhookSenderContract`
- added `SpatieFeedbackWebhookSender`
- changed `WebhookFeedbackChannelDriver` to call the x-feedback webhook sender seam instead of calling Spatie directly
- wrapped `spatie/laravel-webhook-server` behind the default webhook sender implementation
- added test coverage for email delivery through Laravel Mail
- added test coverage for SMS delivery through `LBHurtado\SMS\Facades\SMS`
- added test coverage proving webhook driver depends on the internal sender seam
- added test coverage proving the default webhook sender wraps Spatie Webhook Server

Decisions:

- `mail` remains the safe Phase 11 compatibility/baseline channel key.
- `email` is the explicit Phase 12 transport channel key.
- `sms` uses `lbhurtado/sms:^2.4.2`.
- `webhook` remains an x-feedback channel from the outside.
- Spatie Webhook Server is an internal implementation detail behind `FeedbackWebhookSenderContract`.

Deferred:

- no delivery persistence
- no webhook routes
- no host package integration
- no durable retry scheduling
- no provider callback idempotency
- no x-journal persistence

## Next Recommended Phase

Phase 16 — Action and Artifact Rendering Policy Baseline.

Recommended scope:

- add action rendering policy DTOs if current intent action payloads need shaping
- add artifact rendering policy DTOs/contracts
- add per-channel rendering decision tests
- prove x-feedback renders supplied actions/artifacts but does not decide actions or store artifacts
- keep artifact storage, x-action dependency, file generation, lifecycle truth ownership, and workflow execution deferred unless explicitly authorized

## Functional Specification Priority

The remaining Wave 3 roadmap is now prioritized against:

```text
/Users/rli/PhpstormProjects/x-change-sandbox/docs/todo/x-feedback/x-feedback_functional_specifications.md
```

Earlier planning documents remain guidance for boundaries, sequencing, and non-goals. The functional specification is the primary checklist for remaining x-feedback capability coverage.

Precedence for future x-feedback slices:

1. Functional specification coverage.
2. Existing architecture invariants and current/target-state documents.
3. This evolution plan and the Compass.
4. Current source code and tests.

The current package already covers the grammar, event mapper, intent, template, dispatch preparation, channel driver, transport driver, retry/freshness, callback mapping, and journal handoff baselines. Remaining work should close the functional specification gaps without making x-feedback own lifecycle truth, workflow decisions, campaign orchestration, audit history, CTA decisions, or artifact storage.

## Functional Specification Coverage Roadmap

### Phase 13 — Preference and Suppression Policy Baseline

Status: Complete.

Functional specification coverage:

- `NotificationPreference`
- suppression/opt-out policy
- quiet-hours policy
- required-channel policy
- freshness-aware non-delivery gates

Recommended scope:

- add preference and suppression DTOs
- add evaluator contract and deterministic evaluator
- evaluate channel enabled/disabled, opt-out, quiet hours, stale intent, and required-channel behavior
- keep decisions advisory and side-effect free

Implemented:

- `FeedbackNotificationPreferenceData`
- `FeedbackQuietHoursData`
- `FeedbackSuppressionPolicyData`
- `FeedbackSuppressionDecisionData`
- `FeedbackSuppressionEvaluatorContract`
- `FeedbackSuppressionEvaluator`
- service-provider binding for `FeedbackSuppressionEvaluatorContract`
- notification preference disabled-channel suppression
- recipient opt-out channel suppression
- disabled-channel suppression
- quiet-hours suppression with timezone support
- intent expiry suppression
- stale intent suppression through `meta.created_at` and policy freshness window
- required-channel advisory allow decisions without overriding hard disabled-channel suppression
- tests proving the evaluator remains side-effect free and independent from persistence, routes, host packages, provider delivery, workflow execution, and journal truth

Deferred:

- no persistence
- no routes
- no provider delivery changes
- no host policy coupling
- no lifecycle truth ownership

### Phase 14 — Notification Route Baseline

Status: Complete.

Functional specification coverage:

- `NotificationRoute`
- route resolution by recipient and channel
- removal of hardcoded channel addresses from recipient data paths

Recommended scope:

- add notification route DTOs
- add route resolver contract and in-memory/config-backed resolver baseline
- support route verification metadata and primary/fallback route ordering
- compose routes with delivery planning without mutating recipients

Implemented:

- `FeedbackNotificationRouteData`
- `FeedbackNotificationRouteResolverContract`
- `FeedbackNotificationRouteResolver`
- package config seam at `x-feedback.notification_routes`
- service-provider binding for `FeedbackNotificationRouteResolverContract`
- recipient route data normalization for scalar, single-array, and list-of-array route definitions
- config-backed route resolution for package consumers without database route storage
- deterministic route ordering by primary, verified, priority, and address
- legacy recipient channel-field fallback while hosts migrate to `NotificationRoute`
- delivery planning composition through the notification route resolver
- route metadata in delivery plan items without mutating recipients
- tests proving route resolution remains independent from persistence, the contact package, package routes, providers, host packages, workflow execution, and journal truth

Deferred:

- no database route book
- no contact package dependency
- no host route synchronization
- no provider delivery changes
- no lifecycle truth ownership

### Phase 15 — Feature Profile and Template Policy Baseline

Status: Complete.

Functional specification coverage:

- feature profiles as institutional experiences
- profile-aware template policy
- event key + feature profile + channel template resolution hardening

Recommended scope:

- add feature profile DTO/policy objects if needed
- strengthen template resolver behavior around profile fallback and channel fallback
- add tests proving feature profiles are not languages and do not own business meaning

Implemented:

- `FeedbackFeatureProfileData`
- `FeedbackTemplateResolutionPolicyData`
- `FeedbackTemplatePolicyResolverContract`
- `FeedbackTemplatePolicyResolver`
- package config seam at `x-feedback.template_policy`
- service-provider binding for `FeedbackTemplatePolicyResolverContract`
- feature-profile variables merged into template rendering without making profiles languages
- feature-profile actions used only when intent and template actions are absent
- profile fallback candidates through explicit policy
- channel fallback candidates through explicit policy
- template resolver metadata for selected feature profile, template profile, and template channel
- fail-closed template registry behavior for mismatched feature profiles
- fail-closed template registry behavior for mismatched channels without explicit policy fallback
- tests proving feature profiles remain presentation/institutional context and do not own business meaning, lifecycle truth, persistence, template authoring UI, or host package coupling

Deferred:

- no template authoring UI
- no template persistence
- no approval/version workflow unless separately authorized
- no lifecycle truth ownership
- no host package coupling

### Phase 16 — Action and Artifact Rendering Policy Baseline

Status: Complete.

Functional specification coverage:

- action rendering support
- artifact rendering support
- per-channel artifact policies: `preview`, `link`, `hide`, `attach`

Recommended scope:

- add action rendering policy DTOs if current intent action payloads need shaping
- add artifact rendering policy DTOs/contracts
- add per-channel rendering decision tests
- prove x-feedback renders supplied actions/artifacts but does not decide actions or store artifacts

Implemented:

- `FeedbackActionRenderingPolicyData`
- `FeedbackArtifactRenderingPolicyData`
- `FeedbackRenderedActionData`
- `FeedbackRenderedArtifactData`
- `FeedbackRenderingDecisionData`
- `FeedbackActionArtifactRendererContract`
- `FeedbackActionArtifactRenderer`
- package config seam at `x-feedback.rendering`
- service-provider binding for `FeedbackActionArtifactRendererContract`
- per-channel action rendering defaults for `sms`, `webhook`, `log`, `null`, and rich channels
- per-channel artifact strategies: `preview`, `link`, `hide`, `attach`
- attachment rendering disabled by default and only enabled by explicit policy
- tests proving x-feedback renders only supplied actions/artifacts and does not decide workflow availability, assign artifact meaning, store artifacts, generate files, or depend on x-action

Deferred:

- no artifact storage
- no x-action dependency unless explicitly authorized
- no file generation beyond portable rendering metadata
- no CTA decision ownership
- no workflow execution

### Phase 17 — Durable Delivery Records Baseline

Status: Complete.

Functional specification coverage:

- `FeedbackDelivery`
- delivery state machine
- attempt counts, max attempts, expiry, provider response preservation
- delivery receipts as communication facts

Recommended scope:

- introduce database-backed x-feedback delivery records if authorized
- preserve append/update semantics appropriate for communication delivery state, not audit truth
- retain x-journal as the system record for audit history
- define idempotency keys for provider callbacks and repeated dispatch attempts

Implemented:

- database migration for `feedback_delivery_records`
- `FeedbackDeliveryRecord` Eloquent model
- extended `FeedbackDeliveryRecordData` with delivery ID, idempotency key, attempt counts, provider response, terminal timestamps, and expiry
- `DatabaseFeedbackDeliveryAttemptRecorder`
- service-provider binding for `FeedbackDeliveryAttemptRecorderContract` to durable database implementation
- package migration loading through `XFeedbackServiceProvider`
- Testbench database isolation with in-memory SQLite and `RefreshDatabase`
- idempotent update semantics by receipt idempotency key
- provider response preservation as communication delivery state
- delivery and failure timestamps for terminal delivery statuses
- read-side lookup by correlation ID and intent key
- tests proving durable records remain communication facts and not x-journal audit truth, settlement truth, lifecycle truth, or workflow mutation

Deferred unless explicitly authorized:

- no Cockpit pages
- no campaign orchestration
- no business lifecycle mutation
- no x-journal dependency
- no retry queueing or provider callback route handling

### Phase 18 — In-App Notification Baseline

Status: Complete.

Functional specification coverage:

- in-app notification state: `unread`, `read`, `archived`, `dismissed`
- mark read, mark unread, and bulk mark read capabilities

Recommended scope:

- add in-app notification model or durable store if Phase 17 persistence exists
- add service contracts for read-state transitions
- prove read-state changes do not mutate delivery truth, workflow truth, or journal truth

Implemented:

- migration adding in-app notification state columns to `feedback_delivery_records`
- `FeedbackInAppNotificationData`
- `FeedbackInAppNotificationStateManagerContract`
- `FeedbackInAppNotificationStateManager`
- service-provider binding for the in-app notification state manager
- default `unread` state for `in_app` durable delivery records
- read/unread/archive/dismiss transitions
- bulk mark-read by recipient type and ID
- recipient notification listing with archived/dismissed filtering by default
- tests proving in-app state changes do not mutate delivery status, workflow truth, lifecycle truth, or journal truth

Deferred:

- no Cockpit notification center page
- no frontend component work unless separately authorized
- no HTTP routes or controllers
- no workflow action execution

### Phase 19 — Operational Monitoring Baseline

Functional specification coverage:

- channel health
- delivery failures
- retry backlog
- provider health signals such as depleted SMS credits or webhook endpoint failures

Recommended scope:

- add channel health aggregation service
- add delivery failure/retry backlog read models
- add tests around health signals from registered drivers and delivery records

Deferred:

- no alert delivery loop unless explicitly authorized
- no dashboard widgets; Cockpit owns pages/widgets

### Phase 20 — Delivery Console API Baseline

Functional specification coverage:

- APIs for delivery status, attempt history, provider responses, and retry actions

Recommended scope:

- add API/resource contracts or package routes only if authorized for this package
- expose read-side delivery console data without creating Cockpit pages
- model retry requests as commands/handoff facts, not automatic workflow decisions

Deferred:

- no Cockpit page ownership
- no broad operator exposure without redaction/authorization rules

### Phase 21 — Credential Resolution Baseline

Functional specification coverage:

- tenant/institution/customer credential ownership
- SMTP, SMS provider, Slack, webhook signing, WhatsApp, and Viber credential resolution

Recommended scope:

- expand credential resolver DTOs/contracts
- resolve credentials dynamically by owner/context/channel
- keep provider secrets out of rendered messages, logs, delivery records, and journal payloads

Deferred:

- no secret storage implementation unless explicitly authorized
- no tenant package dependency unless a host integration slice authorizes it

### Phase 22 — Journal Event Emission / Handoff Integration

Functional specification coverage:

- `feedback.created`
- `feedback.sent`
- `feedback.failed`
- `feedback.expired`
- x-journal remains the system of record

Recommended scope:

- strengthen feedback journal handoff payloads from delivery records and receipts
- add event names and fact shapes for feedback lifecycle communication events
- keep x-feedback journal-ready but not journal-dependent unless an explicit adapter slice authorizes a dependency

Deferred:

- no direct x-journal persistence unless explicitly authorized
- no audit-history ownership in x-feedback

### Phase 23 — UI Component Baseline

Functional specification coverage:

- reusable x-feedback UI components:
  - `NotificationBadge`
  - `NotificationBell`
  - `NotificationList`
  - `NotificationItem`
  - `DeliveryStatusBadge`
  - `DeliveryTimeline`
  - `DeliveryAttemptTable`
  - `ChannelIcon`
  - `RetryDeliveryButton`

Recommended scope:

- create reusable package UI components only after read-side APIs and delivery records are stable
- keep pages owned by Cockpit
- use package read models and API seams rather than embedding lifecycle behavior in UI

Deferred:

- no Notification Center page
- no Claim/Campaign/Settlement page ownership
- no hidden workflow execution in UI components
