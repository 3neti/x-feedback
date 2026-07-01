<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackArtifactRenderingPolicyData extends Data
{
    public const string StrategyPreview = 'preview';

    public const string StrategyLink = 'link';

    public const string StrategyHide = 'hide';

    public const string StrategyAttach = 'attach';

    /**
     * @param  array<int, string>  $allowed_artifact_types
     */
    public function __construct(
        public string $channel,
        public string $strategy = self::StrategyLink,
        public ?int $max_artifacts = null,
        public array $allowed_artifact_types = [],
        public bool $allow_attachments = false,
        public array $meta = [],
    ) {}
}
