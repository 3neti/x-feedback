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

## Next Recommended Phase

Phase 6 — Delivery Attempt Runtime Baseline.

Recommended scope:

- execute prepared delivery plan items through registered channel drivers
- keep durable delivery records deferred unless explicitly authorized
- preserve provider receipt handoff as portable output data
- keep queues, retries, provider SDKs, routes, and host integrations deferred
