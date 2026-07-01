<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackActionArtifactRendererContract;
use LBHurtado\XFeedback\Data\FeedbackActionRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackArtifactRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRenderedActionData;
use LBHurtado\XFeedback\Data\FeedbackRenderedArtifactData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;

final class FeedbackActionArtifactRenderer implements FeedbackActionArtifactRendererContract
{
    public function __construct(
        private readonly array $actionPolicies = [],
        private readonly array $artifactPolicies = [],
    ) {}

    public function render(
        FeedbackIntentData $intent,
        FeedbackChannelData|string $channel,
        ?FeedbackActionRenderingPolicyData $actionPolicy = null,
        ?FeedbackArtifactRenderingPolicyData $artifactPolicy = null,
    ): FeedbackRenderingDecisionData {
        $channelKey = $channel instanceof FeedbackChannelData ? $channel->key : $channel;
        $resolvedActionPolicy = $actionPolicy ?? $this->actionPolicyFor($channelKey);
        $resolvedArtifactPolicy = $artifactPolicy ?? $this->artifactPolicyFor($channelKey);

        return new FeedbackRenderingDecisionData(
            intent_key: $intent->key,
            channel: $channelKey,
            actions: $this->renderActions($intent->message->actions, $channelKey, $resolvedActionPolicy),
            artifacts: $this->renderArtifacts($intent->message->artifacts, $channelKey, $resolvedArtifactPolicy),
            meta: [
                'action_policy' => $resolvedActionPolicy->toArray(),
                'artifact_policy' => $resolvedArtifactPolicy->toArray(),
            ],
        );
    }

    private function actionPolicyFor(string $channel): FeedbackActionRenderingPolicyData
    {
        $policy = $this->policyPayload($this->actionPolicies, $channel);
        $policy['channel'] = $channel;
        $policy['render_as'] ??= $this->defaultActionRendering($channel);
        $policy['max_actions'] ??= $channel === 'sms' ? 1 : null;

        return new FeedbackActionRenderingPolicyData(...$policy);
    }

    private function artifactPolicyFor(string $channel): FeedbackArtifactRenderingPolicyData
    {
        $policy = $this->policyPayload($this->artifactPolicies, $channel);
        $policy['channel'] = $channel;
        $policy['strategy'] ??= $this->defaultArtifactStrategy($channel);
        $policy['allow_attachments'] ??= false;

        return new FeedbackArtifactRenderingPolicyData(...$policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function policyPayload(array $policies, string $channel): array
    {
        $policy = $policies[$channel] ?? $policies['default'] ?? [];

        if ($policy instanceof FeedbackActionRenderingPolicyData || $policy instanceof FeedbackArtifactRenderingPolicyData) {
            return $policy->toArray();
        }

        return is_array($policy) ? $policy : [];
    }

    private function defaultActionRendering(string $channel): string
    {
        return match ($channel) {
            'sms' => FeedbackActionRenderingPolicyData::RenderAsLink,
            'webhook' => FeedbackActionRenderingPolicyData::RenderAsPayload,
            'log', 'null' => FeedbackActionRenderingPolicyData::RenderAsMetadata,
            default => FeedbackActionRenderingPolicyData::RenderAsButton,
        };
    }

    private function defaultArtifactStrategy(string $channel): string
    {
        return match ($channel) {
            'sms', 'null' => FeedbackArtifactRenderingPolicyData::StrategyHide,
            'email', 'mail', 'in_app' => FeedbackArtifactRenderingPolicyData::StrategyPreview,
            default => FeedbackArtifactRenderingPolicyData::StrategyLink,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<int, FeedbackRenderedActionData>
     */
    private function renderActions(array $actions, string $channel, FeedbackActionRenderingPolicyData $policy): array
    {
        $rendered = [];

        foreach ($actions as $action) {
            $key = $this->stringValue($action['key'] ?? $action['id'] ?? null);

            if ($key === null) {
                continue;
            }

            if ($policy->allowed_action_keys !== [] && ! in_array($key, $policy->allowed_action_keys, true)) {
                continue;
            }

            $rendered[] = new FeedbackRenderedActionData(
                key: $key,
                label: $this->stringValue($action['label'] ?? $key) ?? $key,
                target: $this->stringValue($action['target'] ?? $action['url'] ?? null),
                style: $this->stringValue($action['style'] ?? null),
                channel: $channel,
                render_as: $policy->render_as,
                enabled: (bool) ($action['enabled'] ?? $action['available'] ?? true),
                meta: $this->metaWithout($action, ['key', 'id', 'label', 'target', 'url', 'style', 'enabled', 'available']),
            );

            if ($policy->max_actions !== null && count($rendered) >= $policy->max_actions) {
                break;
            }
        }

        return $rendered;
    }

    /**
     * @param  array<int, array<string, mixed>>  $artifacts
     * @return array<int, FeedbackRenderedArtifactData>
     */
    private function renderArtifacts(array $artifacts, string $channel, FeedbackArtifactRenderingPolicyData $policy): array
    {
        $rendered = [];

        foreach ($artifacts as $artifact) {
            $type = $this->stringValue($artifact['type'] ?? $artifact['key'] ?? null);

            if ($type === null) {
                continue;
            }

            if ($policy->allowed_artifact_types !== [] && ! in_array($type, $policy->allowed_artifact_types, true)) {
                continue;
            }

            $rendered[] = $this->renderArtifact($artifact, $type, $channel, $policy);

            if ($policy->max_artifacts !== null && count($rendered) >= $policy->max_artifacts) {
                break;
            }
        }

        return $rendered;
    }

    /**
     * @param  array<string, mixed>  $artifact
     */
    private function renderArtifact(
        array $artifact,
        string $type,
        string $channel,
        FeedbackArtifactRenderingPolicyData $policy,
    ): FeedbackRenderedArtifactData {
        $label = $this->stringValue($artifact['label'] ?? $type) ?? $type;
        $url = $this->stringValue($artifact['url'] ?? $artifact['href'] ?? null);
        $preview = $this->stringValue($artifact['preview'] ?? $artifact['thumbnail'] ?? null);
        $attachment = $policy->allow_attachments
            ? $this->stringValue($artifact['attachment'] ?? null)
            : null;

        if ($policy->strategy === FeedbackArtifactRenderingPolicyData::StrategyHide) {
            $url = null;
            $preview = null;
            $attachment = null;
        }

        if ($policy->strategy === FeedbackArtifactRenderingPolicyData::StrategyLink) {
            $preview = null;
            $attachment = null;
        }

        if ($policy->strategy === FeedbackArtifactRenderingPolicyData::StrategyPreview) {
            $attachment = null;
        }

        return new FeedbackRenderedArtifactData(
            type: $type,
            label: $label,
            channel: $channel,
            strategy: $policy->strategy,
            url: $url,
            preview: $preview,
            attachment: $attachment,
            hidden: $policy->strategy === FeedbackArtifactRenderingPolicyData::StrategyHide,
            meta: $this->metaWithout($artifact, ['type', 'key', 'label', 'url', 'href', 'preview', 'thumbnail', 'attachment']),
        );
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $excluded
     * @return array<string, mixed>
     */
    private function metaWithout(array $payload, array $excluded): array
    {
        foreach ($excluded as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
