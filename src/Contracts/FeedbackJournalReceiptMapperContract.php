<?php

namespace LBHurtado\XFeedback\Contracts;

use LBHurtado\XFeedback\Data\FeedbackDeliveryRecordData;
use LBHurtado\XFeedback\Data\FeedbackJournalReceiptData;
use LBHurtado\XFeedback\Data\FeedbackProviderReceiptData;

interface FeedbackJournalReceiptMapperContract
{
    public function fromRecord(FeedbackDeliveryRecordData $record): FeedbackJournalReceiptData;

    public function fromReceipt(FeedbackProviderReceiptData $receipt): FeedbackJournalReceiptData;

    /**
     * @param  array<int, FeedbackDeliveryRecordData>  $records
     * @return array<int, FeedbackJournalReceiptData>
     */
    public function fromRecords(array $records): array;
}
