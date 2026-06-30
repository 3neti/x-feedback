# X-FEEDBACK COMPASS

## Mission

`3neti/x-feedback` is the notification and communication infrastructure package for the x-change Settlement Operating System.

It answers:

```text
Who should be informed, how, and with what delivery result?
```

It does not decide workflow meaning, execute actions, or own lifecycle truth.

## Current Phase

Wave 3 — x-feedback  
Phase 11 — Channel Driver Architecture Backfill  
Status: Complete  
Last updated: 2026-06-30

## Completed Work

- Created independent package scaffold at `/Users/rli/PhpstormProjects/packages/x-feedback`.
- Established package identity:
  - Composer package: `3neti/x-feedback`
  - Namespace: `LBHurtado\XFeedback`
- Added Laravel package auto-discovery for `LBHurtado\XFeedback\XFeedbackServiceProvider`.
- Added `spatie/laravel-data` as the DTO foundation.
- Added package config at `config/x-feedback.php`.
- Added DTOs:
  - `FeedbackIntentData`
  - `FeedbackRecipientData`
  - `FeedbackChannelData`
  - `FeedbackMessageData`
  - `FeedbackContextData`
  - `FeedbackDeliveryData`
- Added contracts:
  - `FeedbackChannelDriverContract`
  - `FeedbackChannelRegistryContract`
  - `FeedbackDispatcherContract`
  - `FeedbackTemplateResolverContract`
  - `FeedbackCredentialResolverContract`
- Added runtime services:
  - `FeedbackChannelRegistry`
  - `FeedbackDispatcher`
  - `PassthroughFeedbackTemplateResolver`
  - `NullFeedbackCredentialResolver`
  - `NullFeedbackChannelDriver`
- Completed Phase 2 Feedback Event Mapping Baseline:
  - added `FeedbackEventData`
  - added `FeedbackEventMapperContract`
  - added `FeedbackEventMapperRegistryContract`
  - added `FeedbackEventMapperRegistry`
  - added `UnknownFeedbackEventMapperException`
  - added package config mapper extension seam
  - supports runtime mapper registration
  - supports class-string mapper resolution through the Laravel container
  - maps registered feedback events into feedback intents
  - fails closed for unmapped events before delivery dispatch
  - keeps mapping independent from delivery persistence, routes, actions, journal, and lifecycle truth
- Completed Phase 3 Template Resolution Baseline:
  - added `FeedbackTemplateData`
  - added `FeedbackTemplateRegistryContract`
  - added `FeedbackTemplateRegistry`
  - added `FeedbackTemplateResolver`
  - added `UnknownFeedbackTemplateException`
  - added package config template extension seam
  - resolves templates by key, locale, profile, and channel
  - falls back to key-level default templates
  - renders placeholders from template defaults and intent variables
  - preserves intent immutability during template resolution
  - keeps template resolution independent from provider delivery, persistence, routes, and host packages
- Completed Phase 4 Channel Driver Selection and Delivery Planning Baseline:
  - added `FeedbackChannelSelectionPolicyData`
  - added `FeedbackDeliveryPlanData`
  - added `FeedbackDeliveryPlanItemData`
  - added `FeedbackChannelSelectorContract`
  - added `FeedbackDeliveryPlannerContract`
  - added `FeedbackChannelSelector`
  - added `FeedbackDeliveryPlanner`
  - added package-consumer bindings for selector and planner contracts
  - supports allowed, required, preferred, fallback, disabled, and enabled-channel filtering
  - produces recipient/channel delivery plans without resolving channel drivers
  - keeps delivery planning independent from provider delivery, persistence, routes, and host packages
- Completed Phase 5 Delivery Dispatch Preparation and Receipt Handoff Baseline:
  - added `FeedbackDispatchPreparationData`
  - added `FeedbackProviderReceiptData`
  - added `FeedbackDispatchPreparerContract`
  - added `FeedbackReceiptHandoffMapperContract`
  - added `FeedbackDispatchPreparer`
  - added `FeedbackReceiptHandoffMapper`
  - added package-consumer bindings for dispatch preparation and receipt handoff
  - composes template resolution and delivery planning into a side-effect-free dispatch preparation seam
  - maps delivery results into provider receipt handoff payloads without persistence
  - keeps real provider delivery, durable delivery records, queues, routes, and host integrations deferred
- Completed Phase 6 Delivery Attempt Runtime Baseline:
  - added `FeedbackDeliveryAttemptData`
  - added `FeedbackDeliveryAttemptRuntimeContract`
  - added `FeedbackDeliveryAttemptRuntime`
  - added package-consumer binding for the delivery attempt runtime
  - executes prepared delivery plan items through registered channel drivers
  - maps delivery results into provider receipt handoff payloads
  - fails closed for unknown planned delivery channels before later plan items execute
  - keeps durable delivery records, queues, routes, provider SDKs, and host integrations deferred
- Completed Phase 7 Delivery Recording Strategy Baseline:
  - added `FeedbackDeliveryRecordData`
  - added `FeedbackDeliveryAttemptRecorderContract`
  - added `InMemoryFeedbackDeliveryAttemptRecorder`
  - added package-consumer binding for the non-persistent delivery recorder
  - records delivery attempt receipts into non-canonical in-memory records
  - supports read-side lookup by correlation ID and intent key
  - supports recorder reset for tests and short-lived baselines
  - keeps database persistence, x-journal dependency, routes, jobs, and host integrations deferred
- Completed Phase 8 Journal Receipt Handoff Baseline:
  - added `FeedbackJournalReceiptData`
  - added `FeedbackJournalReceiptMapperContract`
  - added `FeedbackJournalReceiptMapper`
  - added package-consumer binding for journal receipt handoff mapping
  - maps delivery records into x-journal-ready feedback receipt facts
  - maps provider receipts into x-journal-ready feedback receipt facts
  - supports batch mapping from delivery records
  - keeps x-journal dependency, persistence, routes, jobs, and host integrations deferred
- Completed Phase 9 Provider Callback Feedback Mapping Baseline:
  - added `FeedbackProviderCallbackData`
  - added `FeedbackProviderCallbackMapperContract`
  - added `FeedbackProviderCallbackMapper`
  - added package-consumer binding for provider callback mapping
  - maps provider callback facts into `FeedbackProviderReceiptData`
  - maps provider callback facts into `FeedbackEventData`
  - normalizes provider callback statuses into delivery statuses
  - keeps webhook routes, provider SDKs, persistence, queues, and host integrations deferred
- Completed Phase 10 Retry and Freshness Policy Baseline:
  - added `FeedbackRetryPolicyData`
  - added `FeedbackRetryDecisionData`
  - added `FeedbackRetryFreshnessEvaluatorContract`
  - added `FeedbackRetryFreshnessEvaluator`
  - added package-consumer binding for retry/freshness evaluation
  - classifies retryable, final, expired, exhausted, and pending delivery records
  - calculates next retry timestamps from policy backoff
  - keeps queued retries, persistence, provider SDKs, routes, and host integrations deferred
- Completed Phase 11 Channel Driver Architecture Backfill:
  - reconciled the package roadmap with the original x-feedback todo Phase 2 driver architecture expectations
  - added `FeedbackChannelHealthData`
  - expanded `FeedbackChannelDriverContract` to `send`, `supports`, and `health`
  - added safe baseline drivers for `null`, `log`, `in_app`, `mail`, and `webhook`
  - registered the baseline drivers in package configuration
  - preserved unknown-driver fail-closed behavior
  - kept baseline sends as package-local handoff facts without provider side effects
  - kept provider SDKs, outbound webhook calls, SMTP assumptions, queues, routes, persistence, and host coupling deferred

## Discoveries

- `/Users/rli/PhpstormProjects/packages/x-feedback` did not exist before Wave 3.
- The planning file `x-feedbacl_codex_instructions.md` contains a filename typo but is the active Codex instruction file.
- Phase 1 can be implemented without touching x-change, x-action, x-journal, or any provider package.
- Phase 2 can translate generic event facts into feedback intents without adding real delivery or host package dependencies.
- Phase 3 can render message content without invoking channel drivers or provider delivery.
- Phase 4 can create dry delivery plans without touching the channel driver registry; driver resolution remains a dispatch concern.
- Phase 5 can compose template resolution and delivery planning without changing the existing dispatcher path.
- Provider receipt handoff can be represented as portable data without introducing x-journal or delivery-record persistence.
- Phase 6 can reuse the existing channel driver contract instead of creating a parallel provider API.
- The delivery attempt runtime can execute prepared plans without altering the existing `FeedbackDispatcherContract` behavior.
- Phase 7 can provide a package-local recording seam without making x-feedback the canonical audit log.
- The first recording strategy is intentionally in-memory and non-durable; x-journal handoff remains a later integration slice.
- Phase 8 can produce journal-ready facts without importing or depending on x-journal.
- Journal-ready feedback payloads should be treated as handoff data, not persisted journal entries.
- Phase 9 can normalize provider callback facts without owning provider lifecycle truth.
- Callback mapping can feed existing receipt and event seams without introducing HTTP routes or provider SDKs.
- Phase 10 can produce retry/freshness decisions without scheduling jobs or mutating delivery records.
- Retry policy evaluation can remain deterministic by accepting an explicit `now` timestamp in tests and callers.
- Roadmap correction identified after Phase 10: the original todo plan defines early Channel Driver Architecture with baseline `mail`, `webhook`, `in_app`, `log`, and `null` drivers, while the package currently has only the channel driver seam, registry, dispatcher/runtime, and `null` driver.
- The current package roadmap intentionally kept real delivery provider behavior deferred, but it should still backfill safe baseline drivers before moving to preference and suppression policy.
- Phase 11 can satisfy the original driver-architecture baseline without making `mail` or `webhook` perform real provider delivery.
- Driver `supports` checks are capability signals only; they are not delivery authorization or lifecycle truth.
- `spatie/laravel-webhook-server` is not currently installed in `x-feedback`; webhook transport must either use an internal seam first or receive explicit dependency approval before adding the package.
- The next slice should create explicit `email`, `sms`, and `webhook` transport drivers before moving to preference/suppression policy.

## Risks

- Future integrations must not let x-feedback become workflow authority.
- Real delivery drivers must not bypass delivery tracking once persistence is introduced.
- Message payloads may contain sensitive action, beneficiary, claim, or provider context and will need redaction before operator exposure.
- The null driver is a safe baseline and test seam, not proof of real provider delivery.
- Feedback event mappers can accidentally become business-decision code if they start inferring lifecycle state instead of translating supplied event facts.
- Unknown event mappings fail closed, which is safe but requires host packages to register mappers explicitly.
- Template resolution can expose sensitive variables in rendered content; future host-facing surfaces need redaction and preview rules.
- Repeated template resolution does not imply delivery and must not be treated as a delivery attempt.
- Delivery plans are not durable delivery records. Hosts must not treat a generated plan as a sent, queued, or persisted notification.
- Channel selection policy is routing preparation only; it is not lifecycle truth, authorization, or provider capability validation.
- Dispatch preparations are previews/pre-flight bundles only. They are not delivery attempts and must not be counted as sent or queued messages.
- Provider receipt handoff payloads can contain raw provider responses and require redaction before operator or beneficiary exposure.
- Delivery attempts are executable runtime behavior even without persistence. Hosts must not rely on them for durability until a delivery-recording slice exists.
- Unknown planned channels fail closed at runtime. Hosts should validate configuration before production dispatch paths.
- In-memory delivery records are process-local and not durable. They are useful for tests, previews, and package baselines only.
- Delivery records in x-feedback must not compete with x-journal as canonical audit truth.
- Journal receipt handoff payloads can carry provider and recipient details. Host integrations must apply redaction before broad operator exposure.
- x-feedback must not call x-journal directly unless a later explicit integration slice authorizes a dependency or adapter.
- Provider callbacks may be duplicated by provider retries. Idempotency is not solved in Phase 9.
- Provider-supplied statuses are communication facts only and must not be treated as settlement, reconciliation, or claim lifecycle truth.
- Retry/freshness decisions are advisory. Hosts must not treat them as queued jobs, persisted retry state, or provider execution.
- Backoff policy must be aligned with provider rate limits before production delivery providers are introduced.
- Driver architecture drift creates confusion if higher-level policy slices assume concrete channels exist. Close the safe driver baseline before adding recipient preference and suppression policy.
- Adding `mail` or `webhook` drivers can accidentally introduce real provider delivery. The backfill must keep these as framework seams or no-real-provider baselines unless explicitly authorized.
- Baseline `mail` and `webhook` drivers currently return handoff facts only. Hosts must not assume SMTP, HTTP, provider acceptance, or delivery confirmation occurred.
- Introducing real `email`, `sms`, or `webhook` drivers can pull in provider dependencies and transport side effects. Dependency and side-effect boundaries must be explicit before implementation.

## Architectural Decisions

- Use `spatie/laravel-data` for DTOs to stay consistent with x-action and x-journal.
- Use explicit delivery statuses instead of booleans.
- Bind a null channel driver by default for safe package tests.
- Keep Phase 1 non-persistent and provider-free.
- Do not add routes, controllers, models, queues, or host package integrations in Phase 1.
- Keep feedback event mapping separate from delivery dispatch.
- Treat `FeedbackEventData` as a fact supplied by upstream packages, not a lifecycle decision made by x-feedback.
- Bind `FeedbackEventMapperRegistryContract` as the package-consumer seam for event-to-intent mapping.
- Bind `FeedbackTemplateRegistryContract` as the package-consumer seam for template registration.
- Bind `FeedbackTemplateResolverContract` to the template-aware resolver by default.
- Keep template resolution side-effect free and non-persistent.
- Bind `FeedbackChannelSelectorContract` as the deterministic channel filtering and ordering seam.
- Bind `FeedbackDeliveryPlannerContract` as the dry-run delivery planning seam.
- Keep driver resolution inside delivery dispatch; planning may reference unregistered future channel keys without provider side effects.
- Keep delivery planning side-effect free and non-persistent.
- Bind `FeedbackDispatchPreparerContract` as the composition seam for template resolution plus delivery planning.
- Bind `FeedbackReceiptHandoffMapperContract` as the provider-result-to-receipt handoff seam.
- Keep receipt handoff DTOs portable and non-durable until a persistence or x-journal integration slice is explicitly authorized.
- Do not alter the existing `FeedbackDispatcherContract` behavior in Phase 5.
- Bind `FeedbackDeliveryAttemptRuntimeContract` as the package-consumer seam for executing prepared delivery plans.
- Use the existing `FeedbackChannelDriverContract` for Phase 6 delivery attempts.
- Fail closed when a prepared plan references an unregistered channel driver.
- Keep delivery attempt runtime non-persistent and synchronous for the baseline.
- Bind `FeedbackDeliveryAttemptRecorderContract` to `InMemoryFeedbackDeliveryAttemptRecorder` for the Phase 7 baseline.
- Treat `FeedbackDeliveryRecordData` as a non-canonical communication record, not a durable journal entry.
- Defer database persistence and x-journal handoff until explicitly authorized.
- Bind `FeedbackJournalReceiptMapperContract` as the package-consumer seam for x-journal-ready receipt facts.
- Treat `FeedbackJournalReceiptData` as a portable handoff payload, not a durable journal entry.
- Keep x-feedback journal-ready but not journal-dependent.
- Bind `FeedbackProviderCallbackMapperContract` as the package-consumer seam for callback fact normalization.
- Map callbacks into existing receipt and event DTOs instead of adding webhook controllers or provider-specific SDK code.
- Keep provider callback mapping non-persistent and side-effect free.
- Bind `FeedbackRetryFreshnessEvaluatorContract` as the package-consumer seam for delivery retry/freshness classification.
- Keep retry/freshness evaluation side-effect free; it returns decisions and never queues retries.
- Keep retry/freshness policy independent from provider SDKs and host queues.
- Treat Phase 11 as a roadmap-correction slice: Channel Driver Architecture Backfill comes before preference/suppression policy.
- Do not introduce real provider SDKs, real outbound webhook calls, queues, routes, durable persistence, or host coupling while backfilling baseline drivers.
- Driver backfill must be test-first and must preserve fail-closed unknown channel behavior.
- Use `FeedbackChannelHealthData` for driver health checks.
- Keep baseline channel drivers side-effect free unless a future provider-delivery slice explicitly authorizes real transport behavior.
- Treat `supports` as driver capability filtering, not business authorization.
- Phase 12 should create explicit `email`, `sms`, and `webhook` transport drivers before preference/suppression policy.
- Do not add `spatie/laravel-webhook-server` or any SMS provider SDK without explicit approval.

## Test Coverage Status

Current coverage:

- feedback intent construction
- message actions/artifacts preservation
- recipient/channel/context preservation
- explicit delivery status list
- dispatcher through registered channel drivers
- unknown channel fail-closed behavior
- core service-provider bindings
- package config default
- architecture safety boundaries
- green x-feedback package suite: `9 passed, 38 assertions`
- generic feedback event modeling
- registered event-to-intent mapping
- mapper class-string container resolution
- unknown event fail-closed behavior
- event mapping boundary safety
- green x-feedback package suite: `15 passed, 71 assertions`
- feedback template modeling
- locale/profile/channel template resolution
- unknown template fail-closed behavior
- placeholder rendering
- template resolver immutability
- template resolution boundary safety
- green x-feedback package suite: `22 passed, 99 assertions`
- channel selection policy modeling
- enabled/allowed/disabled/required/preferred/fallback channel selection
- delivery plan item generation per recipient and selected channel
- proof that delivery planning does not resolve provider/channel drivers
- selector and planner package-consumer bindings
- delivery planning boundary safety
- green focused Phase 4 suite: `7 passed, 37 assertions`
- green x-feedback package suite: `29 passed, 136 assertions`
- dispatch preparation composition of template resolution and delivery planning
- proof that dispatch preparation does not dispatch provider delivery
- provider receipt handoff DTO modeling
- delivery-result to provider-receipt handoff mapping
- dispatch preparation and receipt handoff package-consumer bindings
- dispatch preparation boundary safety
- green focused Phase 5 suite: `6 passed, 44 assertions`
- green x-feedback package suite: `35 passed, 180 assertions`
- prepared delivery plan execution through registered channel drivers
- default null-driver delivery attempt execution
- unknown planned channel fail-closed behavior before later side effects
- delivery attempt receipt handoff generation
- delivery attempt runtime package-consumer binding
- delivery attempt runtime boundary safety
- green focused Phase 6 suite: `5 passed, 28 assertions`
- green x-feedback package suite: `40 passed, 208 assertions`
- delivery record DTO modeling
- delivery attempt recording through recorder seam
- in-memory delivery lookup by correlation ID and intent key
- recorder reset without mutating attempt data
- non-persistent recorder package-consumer binding
- delivery recording boundary safety
- green focused Phase 7 suite: `6 passed, 31 assertions`
- green x-feedback package suite: `46 passed, 239 assertions`
- journal-ready receipt handoff DTO modeling
- delivery record to journal-ready receipt mapping
- provider receipt to journal-ready receipt mapping
- batch delivery record handoff mapping
- journal receipt mapper package-consumer binding
- journal handoff boundary safety
- green focused Phase 8 suite: `6 passed, 41 assertions`
- green x-feedback package suite: `52 passed, 280 assertions`
- provider callback DTO modeling
- provider callback to provider receipt mapping
- provider callback to feedback event mapping
- provider callback status normalization
- provider callback mapper package-consumer binding
- provider callback mapping boundary safety
- green focused Phase 9 suite: `6 passed, 44 assertions`
- green x-feedback package suite: `58 passed, 324 assertions`
- retry/freshness policy DTO modeling
- retryable delivery record classification
- final delivery record classification
- stale delivery record expiration classification
- max-attempt exhaustion classification
- retry/freshness evaluator package-consumer binding
- retry/freshness boundary safety
- green focused Phase 10 suite: `7 passed, 33 assertions`
- green x-feedback package suite: `65 passed, 357 assertions`
- baseline channel driver registry resolution for `null`, `log`, `in_app`, `mail`, and `webhook`
- unknown baseline channel fail-closed behavior
- baseline driver health checks
- baseline driver supports/capability checks
- proof that baseline driver sends remain package-local handoff facts without provider side effects
- channel driver architecture boundary safety
- green focused Phase 11 suite: `18 passed, 86 assertions`
- green x-feedback package suite: `83 passed, 443 assertions`

## Next Recommended Phase

Phase 12 — Transport Driver Baseline.

Recommended scope:

- create explicit `email`, `sms`, and `webhook` drivers
- decide whether `mail` remains an alias/compatibility key for `email`
- keep drivers behind `FeedbackChannelDriverContract`
- define SMS and webhook sender seams without hardcoding provider SDKs
- explicitly decide whether to add `spatie/laravel-webhook-server`; it is absent from current `composer.json`
- preserve fail-closed unknown-driver behavior
- keep durable persistence, queues, routes, and host integrations deferred unless explicitly authorized

Deferred after Phase 12:

- Phase 13 — Preference and Suppression Policy Baseline
- define recipient/channel preference DTOs
- evaluate suppression, opt-out, quiet-hours, and required-channel policy
- keep persistence, provider SDKs, queues, routes, and host integrations deferred unless explicitly authorized

## Open Questions

- Which host event should be the first live mapper: claim succeeded, claim failed, disbursement failed, or operator alert?
- Should durable delivery records belong directly in x-feedback or wait for x-journal receipt integration?
- Which real transport channel should be implemented first beyond safe baseline handoff behavior after the explicit `email`, `sms`, and `webhook` driver skeletons exist?
- Should hosts be allowed to plan unregistered channel keys for future drivers, or should planning become fail-closed once real providers are introduced?
- Should delivery records eventually be persisted directly in x-feedback, or should durable history live only in x-journal?
- Which provider callback shape should become the first host adapter: SMS delivery receipt, email bounce, webhook acknowledgement, or operator alert callback?
- Should provider callback idempotency belong in x-feedback delivery records, x-journal, or host adapters?
- Should retry/freshness decisions eventually be recorded in x-feedback, x-journal, or only host orchestration state?
- When a future provider-delivery slice is authorized, should baseline `mail` and `webhook` become true sending seams through Laravel Mail / HTTP, or should separate provider-specific drivers be introduced?
- Should Phase 12 add `spatie/laravel-webhook-server`, or should webhook delivery start with an internal sender contract and defer the dependency?
