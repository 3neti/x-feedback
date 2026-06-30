<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;

final class FeedbackTemplateResolver implements FeedbackTemplateResolverContract
{
    public function __construct(
        private readonly FeedbackTemplateRegistryContract $templates,
    ) {}

    public function resolve(FeedbackIntentData $intent): FeedbackIntentData
    {
        if ($intent->message->template === null || $intent->message->template === '') {
            return $intent;
        }

        $template = $this->templates->template(
            key: $intent->message->template,
            locale: $intent->message->locale,
            profile: $this->profile($intent),
            channel: $this->channel($intent),
        );

        $variables = array_merge($template->variables, $intent->message->variables);

        return new FeedbackIntentData(
            key: $intent->key,
            message: new FeedbackMessageData(
                title: $this->render($template->title, $variables),
                body: $this->render($template->body, $variables),
                summary: $template->summary === null ? null : $this->render($template->summary, $variables),
                locale: $intent->message->locale ?? $template->locale,
                template: $intent->message->template,
                variables: $variables,
                actions: $intent->message->actions === [] ? $template->actions : $intent->message->actions,
                artifacts: $intent->message->artifacts === [] ? $template->artifacts : $intent->message->artifacts,
                meta: array_merge($template->meta, $intent->message->meta),
            ),
            recipients: $intent->recipients,
            channels: $intent->channels,
            context: $intent->context,
            priority: $intent->priority,
            expires_at: $intent->expires_at,
            meta: $intent->meta,
        );
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

    private function channel(FeedbackIntentData $intent): ?string
    {
        $channel = $intent->channels[0]->key ?? null;

        return is_scalar($channel) ? (string) $channel : null;
    }
}
