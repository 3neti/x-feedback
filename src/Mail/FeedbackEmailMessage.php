<?php

namespace LBHurtado\XFeedback\Mail;

use Illuminate\Mail\Mailable;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;

final class FeedbackEmailMessage extends Mailable
{
    public function __construct(
        public readonly FeedbackIntentData $intent,
        public readonly FeedbackRecipientData $recipient,
        public readonly FeedbackChannelData $channel,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->intent->message->title)
            ->html($this->intent->message->body);
    }
}
