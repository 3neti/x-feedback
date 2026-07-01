<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplatePolicyResolverContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackTemplateData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackTemplateException;

final class FeedbackTemplateResolver implements FeedbackTemplateResolverContract
{
    public function __construct(
        private readonly FeedbackTemplateRegistryContract $templates,
        private readonly FeedbackTemplatePolicyResolverContract $policy,
    ) {}

    public function resolve(FeedbackIntentData $intent): FeedbackIntentData
    {
        if ($intent->message->template === null || $intent->message->template === '') {
            return $intent;
        }

        $template = $this->template($intent);

        $variables = array_merge($this->policy->variablesFor($intent), $template->variables, $intent->message->variables);
        $featureProfile = $this->profile($intent);

        return new FeedbackIntentData(
            key: $intent->key,
            message: new FeedbackMessageData(
                title: $this->render($template->title, $variables),
                body: $this->render($template->body, $variables),
                summary: $template->summary === null ? null : $this->render($template->summary, $variables),
                locale: $intent->message->locale ?? $template->locale,
                template: $intent->message->template,
                variables: $variables,
                actions: $this->actions($intent, $template),
                artifacts: $intent->message->artifacts === [] ? $template->artifacts : $intent->message->artifacts,
                meta: array_merge($template->meta, $intent->message->meta, [
                    'feature_profile' => $featureProfile,
                    'template_profile' => $template->profile,
                    'template_channel' => $template->channel,
                ]),
            ),
            recipients: $intent->recipients,
            channels: $intent->channels,
            context: $intent->context,
            priority: $intent->priority,
            expires_at: $intent->expires_at,
            meta: $intent->meta,
        );
    }

    private function template(FeedbackIntentData $intent): FeedbackTemplateData
    {
        foreach ($this->policy->profileCandidatesFor($intent) as $profile) {
            foreach ($this->policy->channelCandidatesFor($intent) as $channel) {
                try {
                    return $this->templates->template(
                        key: (string) $intent->message->template,
                        locale: $intent->message->locale,
                        profile: $profile,
                        channel: $channel,
                    );
                } catch (UnknownFeedbackTemplateException) {
                    // Try the next explicit policy fallback candidate.
                }
            }
        }

        throw UnknownFeedbackTemplateException::forTemplate((string) $intent->message->template);
    }

    private function actions(FeedbackIntentData $intent, FeedbackTemplateData $template): array
    {
        if ($intent->message->actions !== []) {
            return $intent->message->actions;
        }

        if ($template->actions !== []) {
            return $template->actions;
        }

        return $this->policy->actionsFor($intent);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function render(string $value, array $variables): string
    {
        foreach ($variables as $key => $replacement) {
            if (is_scalar($replacement) || $replacement === null) {
                $value = str_replace('{{ '.$key.' }}', (string) $replacement, $value);
                $value = str_replace('{{'.$key.'}}', (string) $replacement, $value);
            }
        }

        return $value;
    }

    private function profile(FeedbackIntentData $intent): ?string
    {
        $profile = $intent->context?->meta['feature_profile'] ?? $intent->meta['feature_profile'] ?? null;

        return is_scalar($profile) ? (string) $profile : null;
    }

}
