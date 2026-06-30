<?php

namespace LBHurtado\XFeedback\Services;

use LBHurtado\XFeedback\Contracts\FeedbackDeliveryPlannerContract;
use LBHurtado\XFeedback\Contracts\FeedbackDispatchPreparerContract;
use LBHurtado\XFeedback\Contracts\FeedbackTemplateResolverContract;
use LBHurtado\XFeedback\Data\FeedbackChannelSelectionPolicyData;
use LBHurtado\XFeedback\Data\FeedbackDispatchPreparationData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;

final class FeedbackDispatchPreparer implements FeedbackDispatchPreparerContract
{
    public function __construct(
        private readonly FeedbackTemplateResolverContract $templateResolver,
        private readonly FeedbackDeliveryPlannerContract $deliveryPlanner,
    ) {}

    public function prepare(FeedbackIntentData $intent, ?FeedbackChannelSelectionPolicyData $policy = null): FeedbackDispatchPreparationData
    {
        $resolvedIntent = $this->templateResolver->resolve($intent);
        $plan = $this->deliveryPlanner->plan($resolvedIntent, $policy);

        return new FeedbackDispatchPreparationData(
            intent_key: $resolvedIntent->key,
            intent: $resolvedIntent,
            plan: $plan,
            correlation_id: $resolvedIntent->context?->correlation_id,
            causation_id: $resolvedIntent->context?->causation_id,
            meta: [
                'event_type' => $resolvedIntent->context?->event_type,
                'source' => $resolvedIntent->context?->source,
                'subject_type' => $resolvedIntent->context?->subject_type,
                'subject_id' => $resolvedIntent->context?->subject_id,
            ],
        );
    }
}
