<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackTemplateRegistryContract;
use LBHurtado\XFeedback\Data\FeedbackTemplateData;
use LBHurtado\XFeedback\Exceptions\UnknownFeedbackTemplateException;

final class FeedbackTemplateRegistry implements FeedbackTemplateRegistryContract
{
    /**
     * @var array<string, array<int, FeedbackTemplateData>>
     */
    private array $templates = [];

    /**
     * @param  array<int, array<string, mixed>|FeedbackTemplateData>  $templates
     */
    public function __construct(array $templates = [])
    {
        foreach ($templates as $template) {
            $this->register($template instanceof FeedbackTemplateData ? $template : new FeedbackTemplateData(...$template));
        }
    }

    public function register(FeedbackTemplateData $template): void
    {
        $this->templates[$template->key] ??= [];
        $this->templates[$template->key][] = $template;
    }

    public function template(
        string $key,
        ?string $locale = null,
        ?string $profile = null,
        ?string $channel = null,
    ): FeedbackTemplateData {
        $templates = $this->templates[$key] ?? [];

        if ($templates === []) {
            throw UnknownFeedbackTemplateException::forTemplate($key);
        }

        return $this->bestMatch($templates, $locale, $profile, $channel)
            ?? throw UnknownFeedbackTemplateException::forTemplate($key);
    }

    /**
     * @param  array<int, FeedbackTemplateData>  $templates
     */
    private function bestMatch(array $templates, ?string $locale, ?string $profile, ?string $channel): ?FeedbackTemplateData
    {
        $templates = array_values(array_filter(
            $templates,
            fn (FeedbackTemplateData $template): bool => $this->compatible($template->locale, $locale)
                && $this->compatible($template->profile, $profile)
                && $this->compatible($template->channel, $channel),
        ));

        usort(
            $templates,
            fn (FeedbackTemplateData $a, FeedbackTemplateData $b): int => $this->score($b, $locale, $profile, $channel)
                <=> $this->score($a, $locale, $profile, $channel),
        );

        return $templates[0] ?? null;
    }

    private function compatible(?string $templateValue, ?string $requestedValue): bool
    {
        return $templateValue === null || $templateValue === $requestedValue;
    }

    private function score(FeedbackTemplateData $template, ?string $locale, ?string $profile, ?string $channel): int
    {
        $score = 0;

        $score += $this->dimensionScore($template->locale, $locale, 4);
        $score += $this->dimensionScore($template->profile, $profile, 2);
        $score += $this->dimensionScore($template->channel, $channel, 1);

        return $score;
    }

    private function dimensionScore(?string $templateValue, ?string $requestedValue, int $weight): int
    {
        if ($templateValue === null) {
            return 0;
        }

        return $templateValue === $requestedValue ? $weight : -100;
    }
}
