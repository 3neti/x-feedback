<?php

namespace LBHurtado\XFeedback\Mail;

use Illuminate\Mail\Mailable;
use LBHurtado\XFeedback\Data\FeedbackChannelData;
use LBHurtado\XFeedback\Data\FeedbackIntentData;
use LBHurtado\XFeedback\Data\FeedbackRecipientData;
use LBHurtado\XFeedback\Data\FeedbackRenderingDecisionData;

final class FeedbackEmailMessage extends Mailable
{
    public function __construct(
        public readonly FeedbackIntentData $intent,
        public readonly FeedbackRecipientData $recipient,
        public readonly FeedbackChannelData $channel,
        public readonly FeedbackRenderingDecisionData $decision,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->intent->message->title)
            ->view('x-feedback::emails.feedback');
    }
}
