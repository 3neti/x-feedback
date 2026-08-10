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
            ->html($this->renderHtml());
    }

    private function renderHtml(): string
    {
        $actions = '';

        foreach ($this->decision->actions as $action) {
            if (! $action->enabled || $action->target === null) {
                continue;
            }

            $actions .= sprintf(
                '<div style="margin-top:24px;"><a href="%s" style="display:inline-block;border-radius:12px;background:#0f172a;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">%s</a></div>',
                $this->escape($action->target),
                $this->escape($action->label),
            );
        }

        $artifacts = '';

        foreach ($this->decision->artifacts as $artifact) {
            if ($artifact->hidden) {
                continue;
            }

            $label = $artifact->url !== null
                ? sprintf(
                    '<a href="%s" style="color:#0f172a;text-decoration:none;font-size:14px;font-weight:700;">%s →</a>',
                    $this->escape($artifact->url),
                    $this->escape($artifact->label),
                )
                : sprintf(
                    '<span style="font-size:14px;font-weight:700;">%s</span>',
                    $this->escape($artifact->label),
                );
            $preview = $artifact->preview !== null
                ? sprintf(
                    '<div style="margin-top:4px;font-size:12px;line-height:1.5;color:#64748b;">%s</div>',
                    $this->escape($artifact->preview),
                )
                : '';

            $artifacts .= sprintf(
                '<div style="margin-top:8px;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;background:#f8fafc;">%s%s</div>',
                $label,
                $preview,
            );
        }

        $evidence = $artifacts === ''
            ? ''
            : '<div style="margin-top:28px;padding-top:22px;border-top:1px solid #e2e8f0;"><div style="margin-bottom:12px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Captured evidence</div>'.$artifacts.'</div>';

        return sprintf(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>%s</title></head><body style="margin:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif;"><div style="max-width:640px;margin:0 auto;padding:32px 16px;"><div style="overflow:hidden;border:1px solid #e2e8f0;border-radius:20px;background:#ffffff;box-shadow:0 16px 40px rgba(15,23,42,.08);"><div style="padding:24px 28px;background:#0f172a;color:#ffffff;"><div style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;">x-change</div><h1 style="margin:10px 0 0;font-size:24px;line-height:1.25;">%s</h1></div><div style="padding:28px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#334155;">%s</p>%s%s<p style="margin:28px 0 0;font-size:11px;line-height:1.6;color:#64748b;">Private evidence remains protected. Sign in to Cockpit to review it. Access is authorized and recorded.</p></div></div></div></body></html>',
            $this->escape($this->intent->message->title),
            $this->escape($this->intent->message->title),
            nl2br($this->escape($this->intent->message->body)),
            $actions,
            $evidence,
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
