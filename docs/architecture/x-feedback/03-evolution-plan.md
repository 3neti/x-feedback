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

## Next Recommended Phase

Phase 4 — Channel Driver Selection and Delivery Planning Baseline.

Recommended scope:

- channel selection policy DTOs/contracts
- delivery plan DTOs
- no real provider delivery
- no persistence
