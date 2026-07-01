<?php

use LBHurtado\XFeedback\Contracts\FeedbackActionArtifactRendererContract;
use LBHurtado\XFeedback\Data\FeedbackActionRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackArtifactRenderingPolicyData;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackContextData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackMessageData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;
use LBHurtado\XFeedback\Services\FeedbackActionArtifactRenderer;

it('models action and artifact rendering policies as presentation rules only', function () {
    $actionPolicy = new FeedbackActionRenderingPolicyData(
        channel: 'sms',
        render_as: FeedbackActionRenderingPolicyData::RenderAsLink,
        max_actions: 1,
        allowed_action_keys: ['claim.view'],
        meta: ['source' => 'host-policy'],
    );

    $artifactPolicy = new FeedbackArtifactRenderingPolicyData(
        channel: 'email',
        strategy: FeedbackArtifactRenderingPolicyData::StrategyPreview,
        max_artifacts: 2,
        allowed_artifact_types: ['receipt'],
        allow_attachments: false,
        meta: ['sensitivity' => 'controlled'],
    );

    expect($actionPolicy->channel)->toBe('sms')
        ->and($actionPolicy->render_as)->toBe(FeedbackActionRenderingPolicyData::RenderAsLink)
        ->and($actionPolicy->allowed_action_keys)->toBe(['claim.view'])
        ->and($artifactPolicy->channel)->toBe('email')
        ->and($artifactPolicy->strategy)->toBe(FeedbackArtifactRenderingPolicyData::StrategyPreview)
        ->and($artifactPolicy->allow_attachments)->toBeFalse();
});

it('renders only supplied actions without deciding workflow availability', function () {
    $intent = feedbackRenderingIntent(actions: [
        [
            'key' => 'claim.view',
            'label' => 'View claim',
            'target' => 'https://example.test/claims/claim-1',
            'style' => 'primary',
            'enabled' => true,
        ],
        [
            'key' => 'claim.retry',
            'label' => 'Retry claim',
            'target' => 'https://example.test/claims/claim-1/retry',
            'enabled' => false,
        ],
    ]);

    $decision = app(FeedbackActionArtifactRendererContract::class)->render(
        intent: $intent,
        channel: new FeedbackChannelData(key: 'sms'),
        actionPolicy: new FeedbackActionRenderingPolicyData(
            channel: 'sms',
            render_as: FeedbackActionRenderingPolicyData::RenderAsLink,
            max_actions: 1,
            allowed_action_keys: ['claim.view'],
        ),
    );

    expect($decision)->toBeInstanceOf(FeedbackRenderingDecisionData::class)
        ->and($decision->channel)->toBe('sms')
        ->and($decision->actions)->toHaveCount(1)
        ->and($decision->actions[0]->key)->toBe('claim.view')
        ->and($decision->actions[0]->label)->toBe('View claim')
        ->and($decision->actions[0]->target)->toBe('https://example.test/claims/claim-1')
        ->and($decision->actions[0]->render_as)->toBe(FeedbackActionRenderingPolicyData::RenderAsLink)
        ->and($decision->actions[0]->enabled)->toBeTrue()
        ->and($intent->message->actions)->toHaveCount(2);
});

it('applies channel defaults for action presentation without inventing actions', function () {
    $intent = feedbackRenderingIntent(actions: [
        ['key' => 'claim.view', 'label' => 'View claim', 'target' => 'https://example.test/claims/claim-1'],
        ['key' => 'help', 'label' => 'Help', 'target' => 'https://example.test/help'],
    ]);

    $decision = app(FeedbackActionArtifactRendererContract::class)->render($intent, 'sms');

    expect($decision->actions)->toHaveCount(1)
        ->and($decision->actions[0]->key)->toBe('claim.view')
        ->and($decision->actions[0]->render_as)->toBe(FeedbackActionRenderingPolicyData::RenderAsLink)
        ->and(collect($decision->actions)->pluck('key')->all())->not->toContain('invented.next-step');
});

it('renders artifacts with preview strategy for rich channels without storing artifacts', function () {
    $intent = feedbackRenderingIntent(artifacts: [
        [
            'type' => 'receipt',
            'label' => 'Claim receipt',
            'url' => 'https://example.test/artifacts/receipt-1',
            'preview' => 'Claim receipt preview',
            'storage_id' => 'artifact-1',
        ],
    ]);

    $decision = app(FeedbackActionArtifactRendererContract::class)->render($intent, 'email');

    expect($decision->artifacts)->toHaveCount(1)
        ->and($decision->artifacts[0]->type)->toBe('receipt')
        ->and($decision->artifacts[0]->label)->toBe('Claim receipt')
        ->and($decision->artifacts[0]->strategy)->toBe(FeedbackArtifactRenderingPolicyData::StrategyPreview)
        ->and($decision->artifacts[0]->preview)->toBe('Claim receipt preview')
        ->and($decision->artifacts[0]->hidden)->toBeFalse()
        ->and($decision->artifacts[0]->meta['storage_id'])->toBe('artifact-1')
        ->and($intent->message->artifacts[0]['storage_id'])->toBe('artifact-1');
});

it('hides artifacts for sms by default and does not expose artifact references', function () {
    $intent = feedbackRenderingIntent(artifacts: [
        [
            'type' => 'receipt',
            'label' => 'Claim receipt',
            'url' => 'https://example.test/artifacts/receipt-1',
            'preview' => 'Claim receipt preview',
        ],
    ]);

    $decision = app(FeedbackActionArtifactRendererContract::class)->render($intent, 'sms');

    expect($decision->artifacts)->toHaveCount(1)
        ->and($decision->artifacts[0]->strategy)->toBe(FeedbackArtifactRenderingPolicyData::StrategyHide)
        ->and($decision->artifacts[0]->hidden)->toBeTrue()
        ->and($decision->artifacts[0]->url)->toBeNull()
        ->and($decision->artifacts[0]->preview)->toBeNull()
        ->and($decision->artifacts[0]->attachment)->toBeNull();
});

it('requires explicit attachment policy before rendering artifact attachments', function () {
    $intent = feedbackRenderingIntent(artifacts: [
        [
            'type' => 'receipt',
            'label' => 'Claim receipt',
            'url' => 'https://example.test/artifacts/receipt-1',
            'attachment' => 'receipt-1.pdf',
        ],
    ]);

    $withoutAttachmentPermission = app(FeedbackActionArtifactRendererContract::class)->render(
        intent: $intent,
        channel: 'email',
        artifactPolicy: new FeedbackArtifactRenderingPolicyData(
            channel: 'email',
            strategy: FeedbackArtifactRenderingPolicyData::StrategyAttach,
            allow_attachments: false,
        ),
    );

    $withAttachmentPermission = app(FeedbackActionArtifactRendererContract::class)->render(
        intent: $intent,
        channel: 'email',
        artifactPolicy: new FeedbackArtifactRenderingPolicyData(
            channel: 'email',
            strategy: FeedbackArtifactRenderingPolicyData::StrategyAttach,
            allow_attachments: true,
        ),
    );

    expect($withoutAttachmentPermission->artifacts[0]->attachment)->toBeNull()
        ->and($withoutAttachmentPermission->artifacts[0]->url)->toBe('https://example.test/artifacts/receipt-1')
        ->and($withAttachmentPermission->artifacts[0]->attachment)->toBe('receipt-1.pdf');
});

it('filters artifacts by presentation policy without assigning artifact meaning', function () {
    $intent = feedbackRenderingIntent(artifacts: [
        ['type' => 'receipt', 'label' => 'Claim receipt', 'url' => 'https://example.test/receipt'],
        ['type' => 'selfie', 'label' => 'Beneficiary selfie', 'url' => 'https://example.test/selfie'],
    ]);

    $decision = app(FeedbackActionArtifactRendererContract::class)->render(
        intent: $intent,
        channel: 'webhook',
        artifactPolicy: new FeedbackArtifactRenderingPolicyData(
            channel: 'webhook',
            strategy: FeedbackArtifactRenderingPolicyData::StrategyLink,
            allowed_artifact_types: ['receipt'],
            max_artifacts: 1,
        ),
    );

    expect($decision->artifacts)->toHaveCount(1)
        ->and($decision->artifacts[0]->type)->toBe('receipt')
        ->and($decision->artifacts[0]->strategy)->toBe(FeedbackArtifactRenderingPolicyData::StrategyLink)
        ->and($decision->artifacts[0]->url)->toBe('https://example.test/receipt')
        ->and($decision->artifacts[0]->meta)->not->toHaveKey('meaning')
        ->and($intent->message->artifacts)->toHaveCount(2);
});

it('keeps rendering policy independent from x-action artifact storage file generation and lifecycle truth', function () {
    $packageRoot = dirname(__DIR__, 2);

    expect(app(FeedbackActionArtifactRendererContract::class))->toBeInstanceOf(FeedbackActionArtifactRenderer::class)
        ->and(is_dir($packageRoot.'/database'))->toBeFalse()
        ->and(is_dir($packageRoot.'/routes'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Models'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/Actions'))->toBeFalse()
        ->and(is_dir($packageRoot.'/src/ArtifactStorage'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XAction\\XActionServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XJournal\\XJournalServiceProvider'))->toBeFalse()
        ->and(class_exists('LBHurtado\\XChange\\XChangeServiceProvider'))->toBeFalse();
});

function feedbackRenderingIntent(array $actions = [], array $artifacts = []): FeedbackIntentData
{
    return new FeedbackIntentData(
        key: 'claim.approved.claimant',
        message: new FeedbackMessageData(
            title: 'Claim approved',
            body: 'Your claim is ready.',
            actions: $actions,
            artifacts: $artifacts,
        ),
        channels: [new FeedbackChannelData(key: 'sms')],
        context: new FeedbackContextData(
            event_type: 'claim.approved',
            correlation_id: 'corr-1',
            subject_type: 'claim',
            subject_id: 'claim-1',
        ),
    );
}
