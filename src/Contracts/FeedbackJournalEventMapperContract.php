<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalEventData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

interface FeedbackJournalEventMapperContract
{
    public function fromRecord(FeedbackDeliveryRecordData $record): FeedbackJournalEventData;

    public function fromReceipt(FeedbackProviderReceiptData $receipt): FeedbackJournalEventData;

    /**
     * @param  array<int, FeedbackDeliveryRecordData>  $records
     * @return array<int, FeedbackJournalEventData>
     */
    public function fromRecords(array $records): array;
}
