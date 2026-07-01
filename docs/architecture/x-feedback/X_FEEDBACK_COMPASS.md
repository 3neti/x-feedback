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
Phase 17 — Durable Delivery Records Baseline
Status: Complete  
Last updated: 2026-07-01

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
- Completed Phase 12 Transport Driver Baseline:
  - added `EmailFeedbackChannelDriver`
  - added `SmsFeedbackChannelDriver`
  - added `FeedbackEmailMessage`
  - added `FeedbackWebhookMessageData`
  - added `FeedbackWebhookSendResultData`
  - added `FeedbackWebhookSenderContract`
  - added `SpatieFeedbackWebhookSender`
  - registered explicit `email`, `sms`, and `webhook` transport channels
  - added `illuminate/mail`
  - added `lbhurtado/sms:^2.4.2`
  - added `spatie/laravel-webhook-server:^3.10`
  - kept Spatie isolated behind the x-feedback webhook sender seam
  - kept webhook as an x-feedback channel from the outside
  - kept persistence, routes, host package integration, and x-journal persistence deferred
- Completed Phase 13 Preference and Suppression Policy Baseline:
  - added `FeedbackNotificationPreferenceData`
  - added `FeedbackQuietHoursData`
  - added `FeedbackSuppressionPolicyData`
  - added `FeedbackSuppressionDecisionData`
  - added `FeedbackSuppressionEvaluatorContract`
  - added `FeedbackSuppressionEvaluator`
  - added package-consumer binding for suppression evaluation
  - evaluates disabled channels, recipient opt-outs, disabled notification preferences, quiet hours, expired intents, stale intents, and required-channel advisory decisions
  - keeps preference and suppression evaluation deterministic, advisory, side-effect free, and independent from persistence, routes, provider delivery, host packages, workflow execution, and journal truth
- Completed Phase 14 Notification Route Baseline:
  - added `FeedbackNotificationRouteData`
  - added `FeedbackNotificationRouteResolverContract`
  - added `FeedbackNotificationRouteResolver`
  - added package config seam at `x-feedback.notification_routes`
  - added package-consumer binding for notification route resolution
  - resolves recipient route data without requiring hardcoded recipient channel fields
  - resolves config-backed routes without adding a database route book
  - orders routes deterministically by primary flag, verification, priority, and address
  - composes notification route resolution into delivery planning
  - preserves legacy recipient `email`/`phone` fallback while hosts migrate to `NotificationRoute`
  - keeps route resolution independent from persistence, the contact package, package routes, provider delivery, host packages, workflow execution, lifecycle truth, and journal truth
- Completed Phase 15 Feature Profile and Template Policy Baseline:
  - added `FeedbackFeatureProfileData`
  - added `FeedbackTemplateResolutionPolicyData`
  - added `FeedbackTemplatePolicyResolverContract`
  - added `FeedbackTemplatePolicyResolver`
  - added package config seam at `x-feedback.template_policy`
  - added package-consumer binding for template policy resolution
  - models feature profiles as institutional/presentation experiences, not languages
  - merges feature-profile variables into template rendering
  - uses feature-profile actions only when intent and template actions are absent
  - supports explicit profile fallback and channel fallback candidates
  - records selected feature profile, template profile, and template channel in resolved message metadata
  - hardens template registry matching so mismatched profiles/channels fail closed without explicit fallback policy
  - keeps feature profile/template policy independent from persistence, template authoring UI, approval/version workflow, host packages, workflow execution, lifecycle truth, and journal truth
- Completed Phase 16 Action and Artifact Rendering Policy Baseline:
  - added `FeedbackActionRenderingPolicyData`
  - added `FeedbackArtifactRenderingPolicyData`
  - added `FeedbackRenderedActionData`
  - added `FeedbackRenderedArtifactData`
  - added `FeedbackRenderingDecisionData`
  - added `FeedbackActionArtifactRendererContract`
  - added `FeedbackActionArtifactRenderer`
  - added package config seam at `x-feedback.rendering`
  - added package-consumer binding for action/artifact rendering
  - renders only supplied actions; it does not decide available workflow actions
  - supports per-channel action presentation defaults for SMS links, webhook payloads, metadata-only channels, and rich-channel buttons
  - supports per-channel artifact strategies: `preview`, `link`, `hide`, and `attach`
  - keeps artifact attachments disabled unless an explicit rendering policy enables them
  - keeps action/artifact rendering independent from x-action, artifact storage, file generation, workflow execution, lifecycle truth, and host packages
- Completed Phase 17 Durable Delivery Records Baseline:
  - added `feedback_delivery_records` migration
  - added `FeedbackDeliveryRecord` model
  - extended `FeedbackDeliveryRecordData` with durable record metadata
  - added `DatabaseFeedbackDeliveryAttemptRecorder`
  - bound `FeedbackDeliveryAttemptRecorderContract` to the durable database recorder
  - loaded package migrations through `XFeedbackServiceProvider`
  - configured package tests for SQLite + `RefreshDatabase`
  - records provider receipts as durable communication delivery state
  - preserves provider responses, provider message IDs, provider statuses, correlation IDs, causation IDs, expiry, max attempts, and attempt counts
  - updates existing delivery records by idempotency key instead of duplicating repeated attempts
  - keeps delivery records independent from x-journal audit truth, routes, controllers, queues, Cockpit pages, campaign orchestration, lifecycle mutation, and host package coupling

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
- `lbhurtado/sms v2.4.2` declares Laravel 13 support and matches the SMS facade chain used in `/Users/rli/PhpstormProjects/txtcmdr`.
- `laravel-notification-channels/webhook` currently does not declare Laravel 13 support, so Phase 12 uses an x-feedback-owned webhook sender seam instead.
- Spatie Webhook Server is Laravel 13-compatible and can be used as the internal webhook transport implementation without leaking into the x-feedback channel API.
- The remaining Wave 3 roadmap must be driven directly by `/Users/rli/PhpstormProjects/x-change-sandbox/docs/todo/x-feedback/x-feedback_functional_specifications.md`.
- Functional specification coverage still has gaps after Phase 12: notification preferences, notification routes, feature-profile hardening, artifact rendering policy, durable delivery records, in-app notification state, operational monitoring, delivery console APIs, credential resolution, journal event handoff strengthening, and reusable UI components.
- Phase 13 closes the first `NotificationPreference` baseline by adding advisory preference/suppression evaluation without persistence or route ownership.
- Phase 14 closes the first `NotificationRoute` baseline by adding a package-local resolver seam without a database route book or contact package dependency.
- Delivery planning now uses the notification route resolver and includes normalized `notification_route` metadata while preserving legacy recipient field fallback.
- Phase 15 found and fixed unsafe template matching: a profile/channel-specific template could previously be selected for a mismatched requested profile/channel when no default existed.
- Feature profiles need a dedicated policy seam because they influence branding, wording, variables, and fallback selection without becoming locales or lifecycle truth.
- Phase 16 can use existing `FeedbackMessageData` action/artifact arrays as upstream payloads; no new CTA authority or artifact ownership model is needed inside x-feedback.
- Channel-specific artifact rendering must default safe: SMS hides artifact references and attachments remain disabled unless policy explicitly allows them.
- Phase 17 replaces the prior in-memory recorder binding with a database-backed implementation while keeping `InMemoryFeedbackDeliveryAttemptRecorder` available as a lightweight seam if a host or test binds it explicitly.
- Durable x-feedback delivery records are communication state only. They can support delivery status and retry/read APIs, but they are not audit/system truth and do not make x-feedback depend on x-journal.

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
- Phase 12 introduces real transport side effects for `email`, `sms`, and queued webhook dispatch. Hosts must not treat successful dispatch as beneficiary receipt, provider confirmation, settlement truth, or workflow completion.
- Spatie webhook dispatch is queued transport infrastructure. It is not webhook callback handling, provider callback verification, delivery persistence, or audit truth.
- SMS delivery depends on host/provider configuration for `lbhurtado/sms`; missing provider credentials will surface at transport time.
- The functional specification includes APIs and reusable UI components, but Cockpit remains responsible for pages. x-feedback UI work must stay component/read-model scoped.
- Durable delivery records in x-feedback can blur with x-journal. x-feedback may own communication delivery state, while x-journal remains audit/system truth.
- Credential resolution introduces secret-handling risk. Credentials must not leak into rendered messages, logs, delivery records, provider responses, or journal handoff payloads.
- Suppression decisions are advisory pre-delivery facts. Hosts must not treat them as workflow authorization, claim status, settlement state, or audit truth.
- Required-channel policy marks a channel as preferred/required for communication planning, but hard suppression gates such as disabled channels still fail closed.
- Notification routes can contain sensitive addresses and verification metadata. Future delivery console, journal handoff, and UI surfaces need redaction before broad operator exposure.
- Legacy recipient `email`/`phone` fallback remains for migration safety. New integrations should prefer `NotificationRoute` data so recipient DTOs do not keep growing channel-specific fields.
- Config-backed notification routes are a package-consumer seam, not production route storage. Durable route books remain deferred.
- Feature-profile variables and branding can contain institution-sensitive presentation data. Future preview/operator surfaces need redaction rules.
- Template policy fallback can accidentally route messages to the wrong institutional wording if hosts configure fallback chains too broadly.
- Template metadata records selected profile/channel for explainability; consumers must not treat it as business lifecycle or authorization truth.
- Rendered action targets and artifact references can expose sensitive workflow or evidence URLs. Future preview, API, and operator surfaces need redaction and authorization rules.
- Action rendering must never be mistaken for x-action capability resolution. x-feedback only shapes actions already supplied by upstream workflow packages.
- Artifact rendering must not become artifact storage, artifact lifecycle, artifact permissioning, or file generation.
- Durable delivery records introduce package persistence and model surface area. Future slices must keep that persistence limited to communication delivery state.
- Delivery record idempotency currently uses explicit receipt idempotency key, provider message ID, or a deterministic fallback. Provider callback adapters must supply stronger provider-specific keys when available.

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
- Phase 12 creates explicit `email`, `sms`, and `webhook` transport drivers before preference/suppression policy.
- Keep `WebhookFeedbackChannelDriver` dependent on `FeedbackWebhookSenderContract`, not directly on Spatie.
- Use `SpatieFeedbackWebhookSender` as the default implementation of `FeedbackWebhookSenderContract`.
- Keep `mail` as the Phase 11 compatibility/baseline key and use `email` as the explicit transport key.
- Prioritize the remaining x-feedback roadmap against the functional specification before adding convenience slices.
- Treat earlier planning documents as boundary and sequencing guidance, not as a substitute for functional specification coverage.
- Preserve this order for the next planned slices unless an explicit architectural decision supersedes it:
  - Phase 13 — Preference and Suppression Policy Baseline
  - Phase 14 — Notification Route Baseline
  - Phase 15 — Feature Profile and Template Policy Baseline
  - Phase 16 — Action and Artifact Rendering Policy Baseline
  - Phase 17 — Durable Delivery Records Baseline
  - Phase 18 — In-App Notification Baseline
  - Phase 19 — Operational Monitoring Baseline
  - Phase 20 — Delivery Console API Baseline
  - Phase 21 — Credential Resolution Baseline
  - Phase 22 — Journal Event Emission / Handoff Integration
  - Phase 23 — UI Component Baseline
- Delivery console APIs and UI components must expose communication facts only; they must not decide workflow actions, settlement state, campaign state, or audit truth.
- Bind `FeedbackSuppressionEvaluatorContract` as the package-consumer seam for advisory preference and suppression decisions.
- Keep suppression evaluation side-effect free; it returns decisions and never sends, queues, persists, retries, records journal entries, or mutates delivery state.
- Treat disabled channels, opt-outs, disabled preferences, expiry, stale intents, and quiet hours as non-delivery gates before provider delivery.
- Treat required-channel decisions as advisory allow decisions, not as overrides for hard disabled-channel suppression.
- Bind `FeedbackNotificationRouteResolverContract` as the package-consumer seam for recipient/channel route resolution.
- Use `FeedbackNotificationRouteData` as the portable route shape for channel address, verification, primary, priority, and metadata.
- Keep notification route resolution non-persistent and side-effect free.
- Compose delivery planning with `FeedbackNotificationRouteResolverContract` so delivery plan items carry normalized route metadata.
- Preserve legacy recipient channel-field fallback as migration compatibility, not as the preferred target shape.
- Bind `FeedbackTemplatePolicyResolverContract` as the package-consumer seam for feature-profile and template fallback policy.
- Treat feature profiles as institutional presentation context, not languages, lifecycle state, authorization, or workflow meaning.
- Keep template policy side-effect free and non-persistent.
- Template registry matching must fail closed for mismatched profile/channel dimensions unless an explicit template policy fallback selects another candidate.
- Template resolver may merge profile variables and actions into rendered communication payloads, but it must not decide business actions or artifact ownership.
- Bind `FeedbackActionArtifactRendererContract` as the package-consumer seam for supplied action and artifact presentation.
- Treat rendered actions as presentation of upstream CTA/action payloads, not as workflow availability decisions.
- Treat rendered artifacts as presentation of upstream artifact references, not as artifact storage, artifact meaning, artifact lifecycle, or permission authority.
- Keep attachments disabled by default; direct artifact distribution must require explicit rendering policy.
- Bind `FeedbackDeliveryAttemptRecorderContract` to `DatabaseFeedbackDeliveryAttemptRecorder` by default after Phase 17.
- Treat `feedback_delivery_records` as x-feedback communication delivery state, not x-journal audit truth.
- Use idempotent append/update semantics for repeated provider receipts and dispatch attempts.

## Functional Specification Coverage Plan

Primary reference:

```text
/Users/rli/PhpstormProjects/x-change-sandbox/docs/todo/x-feedback/x-feedback_functional_specifications.md
```

Coverage already established or partially established:

- event-driven notification grammar: Phases 1–2
- feedback intents and recipients: Phase 1
- message rendering/templates: Phase 3
- channel routing/planning: Phase 4
- dispatch preparation and receipt handoff: Phase 5
- delivery attempt runtime: Phase 6
- non-durable delivery recording seam: Phase 7
- journal-ready receipt facts: Phase 8
- provider callback fact mapping: Phase 9
- retry/freshness decision baseline: Phase 10
- channel driver architecture and health: Phase 11
- concrete email/SMS/webhook transport baseline: Phase 12
- notification preference and suppression baseline: Phase 13
- notification route baseline: Phase 14
- feature profile and template policy baseline: Phase 15
- action and artifact rendering policy baseline: Phase 16
- durable delivery records baseline: Phase 17

Remaining functional specification coverage should be implemented in this order:

1. Phase 18 — In-App Notification Baseline.
   - Covers unread/read/archived/dismissed state and mark-read capabilities.
2. Phase 19 — Operational Monitoring Baseline.
   - Covers channel health, delivery failures, and retry backlog visibility.
3. Phase 20 — Delivery Console API Baseline.
   - Covers delivery status, attempt history, provider responses, and retry action handoff APIs without Cockpit page ownership.
4. Phase 21 — Credential Resolution Baseline.
   - Covers tenant/institution/customer credential resolution for SMTP, SMS, webhook signing, and future channels.
5. Phase 22 — Journal Event Emission / Handoff Integration.
    - Covers `feedback.created`, `feedback.sent`, `feedback.failed`, and `feedback.expired` handoff facts while x-journal remains system truth.
6. Phase 23 — UI Component Baseline.
    - Covers reusable x-feedback UI components while Cockpit owns pages.

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
- explicit `email`, `sms`, and `webhook` transport driver resolution
- email delivery through Laravel Mail
- SMS delivery through `LBHurtado\SMS\Facades\SMS`
- webhook delivery through `FeedbackWebhookSenderContract`
- default webhook sender wrapper around Spatie Webhook Server
- transport capability/support requirements
- transport baseline boundary safety
- green focused Phase 12 suite: `9 passed, 50 assertions`
- green x-feedback package suite: `91 passed, 485 assertions`
- notification preference DTO modeling
- suppression policy and quiet-hours DTO modeling
- disabled preference suppression
- recipient opt-out suppression
- disabled channel suppression
- quiet-hours suppression with timezone-aware windows
- expired and stale intent suppression
- required-channel advisory allow decisions without overriding hard disabled-channel gates
- suppression evaluator package-consumer binding
- preference/suppression boundary safety
- green focused Phase 13 suite: `8 passed, 40 assertions`
- green x-feedback package suite: `99 passed, 525 assertions`
- notification route DTO modeling
- recipient route data normalization
- config-backed route resolution
- deterministic primary/verified/priority route ordering
- delivery planning composition with normalized route metadata
- legacy recipient field fallback during migration
- notification route resolver package-consumer binding
- notification route boundary safety
- green focused Phase 14 suite: `8 passed, 36 assertions`
- green x-feedback package suite: `107 passed, 561 assertions`
- feature profile DTO modeling
- template resolution policy DTO modeling
- template policy resolver package-consumer binding
- profile fallback through feature-profile policy
- channel fallback through template policy
- profile variables merged into rendered templates
- selected feature/template profile/channel metadata
- fail-closed mismatched profile template selection
- fail-closed mismatched channel template selection without policy fallback
- feature profile/template policy boundary safety
- green focused Phase 15 suite: `8 passed, 31 assertions`
- green x-feedback package suite: `115 passed, 592 assertions`
- action rendering policy DTO modeling
- artifact rendering policy DTO modeling
- action/artifact renderer package-consumer binding
- SMS action rendering as links with default max-action policy
- rich-channel artifact preview rendering
- SMS artifact hiding without exposing artifact references
- explicit attachment policy before rendering artifact attachments
- artifact type filtering without assigning artifact meaning
- action/artifact rendering boundary safety
- green focused Phase 16 suite: `8 passed, 51 assertions`
- green x-feedback package suite: `123 passed, 643 assertions`
- durable delivery record migration loading
- database-backed delivery attempt recorder binding
- provider receipt persistence as communication delivery state
- provider response preservation
- delivery idempotency key generation
- repeated attempt updates without duplicate delivery rows
- attempt count tracking
- max-attempt and expiry preservation
- delivered/failed terminal timestamps
- durable lookup by correlation ID and intent key
- reset behavior for package test isolation
- durable delivery record boundary safety
- green focused Phase 17 suite: `8 passed, 45 assertions`
- green x-feedback package suite: `131 passed, 688 assertions`

## Next Recommended Phase

Phase 18 — In-App Notification Baseline.

Recommended scope:

- add in-app notification state on top of the Phase 17 durable store
- support unread, read, archived, and dismissed states
- add mark-read / mark-unread / archive / dismiss service seams
- keep Cockpit notification center pages, frontend components, workflow mutation, and lifecycle truth ownership out of scope unless explicitly authorized

## Open Questions

- Which host event should be the first live mapper: claim succeeded, claim failed, disbursement failed, or operator alert?
- Which transport channel needs production hardening first: email, SMS, or webhook?
- Should hosts be allowed to plan unregistered channel keys for future drivers, or should planning become fail-closed once real providers are introduced?
- Which provider callback shape should become the first host adapter: SMS delivery receipt, email bounce, webhook acknowledgement, or operator alert callback?
- Should provider callback idempotency belong in x-feedback delivery records, x-journal, or host adapters?
- Should retry/freshness decisions eventually be recorded in x-feedback, x-journal, or only host orchestration state?
- What package API shape should Phase 20 use for delivery console reads without making x-feedback own Cockpit pages?
