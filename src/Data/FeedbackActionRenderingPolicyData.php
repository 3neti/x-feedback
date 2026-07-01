<?php

namespace LBHurtado\XFeedback\Data;

use Spatie\LaravelData\Data;

final class FeedbackActionRenderingPolicyData extends Data
{
    public const string RenderAsButton = 'button';

    public const string RenderAsLink = 'link';

    public const string RenderAsPayload = 'payload';

    public const string RenderAsMetadata = 'metadata';

    /**
     * @param  array<int, string>  $allowed_action_keys
     */
    public function __construct(
        public string $channel,
        public string $render_as = self::RenderAsButton,
        public ?int $max_actions = null,
        public array $allowed_action_keys = [],
        public array $meta = [],
    ) {}
}
