<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryFailureSummaryData;
use LBHurtado\XFeedback\Data\FeedbackOperationalMonitoringSnapshotData;
use LBHurtado\XFeedback\Data\FeedbackRetryBacklogData;
use LBHurtado\XFeedback\Data\FeedbackRetryPolicyData;

interface FeedbackOperationalMonitorContract
{
    public function channelHealth(array $channels = []): array;

    public function failureSummary(): FeedbackDeliveryFailureSummaryData;

    public function retryBacklog(
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackRetryBacklogData;

    public function snapshot(
        array $channels = [],
        ?FeedbackRetryPolicyData $policy = null,
        ?string $now = null,
    ): FeedbackOperationalMonitoringSnapshotData;
}
