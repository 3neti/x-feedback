<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $intent->message->title }}</title>
</head>
<body style="margin:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:640px;margin:0 auto;padding:32px 16px;">
    <div style="overflow:hidden;border:1px solid #e2e8f0;border-radius:20px;background:#ffffff;box-shadow:0 16px 40px rgba(15,23,42,.08);">
        <div style="padding:24px 28px;background:#0f172a;color:#ffffff;">
            <div style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;">x-change</div>
            <h1 style="margin:10px 0 0;font-size:24px;line-height:1.25;">{{ $intent->message->title }}</h1>
        </div>
        <div style="padding:28px;">
            <p style="margin:0;font-size:15px;line-height:1.7;color:#334155;">{!! nl2br(e($intent->message->body)) !!}</p>

            @foreach ($decision->actions as $action)
                @if ($action->enabled && $action->target)
                    <div style="margin-top:24px;">
                        <a href="{{ $action->target }}" style="display:inline-block;border-radius:12px;background:#0f172a;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">{{ $action->label }}</a>
                    </div>
                @endif
            @endforeach

            @php($visibleArtifacts = collect($decision->artifacts)->where('hidden', false))
            @if ($visibleArtifacts->isNotEmpty())
                <div style="margin-top:28px;padding-top:22px;border-top:1px solid #e2e8f0;">
                    <div style="margin-bottom:12px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Captured evidence</div>
                    @foreach ($visibleArtifacts as $artifact)
                        <div style="margin-top:8px;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;background:#f8fafc;">
                            @if ($artifact->url)
                                <a href="{{ $artifact->url }}" style="color:#0f172a;text-decoration:none;font-size:14px;font-weight:700;">{{ $artifact->label }} →</a>
                            @else
                                <span style="font-size:14px;font-weight:700;">{{ $artifact->label }}</span>
                            @endif
                            @if ($artifact->preview)
                                <div style="margin-top:4px;font-size:12px;line-height:1.5;color:#64748b;">{{ $artifact->preview }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <p style="margin:28px 0 0;font-size:11px;line-height:1.6;color:#64748b;">Private evidence remains protected. Sign in to Cockpit to review it. Access is authorized and recorded.</p>
        </div>
    </div>
</div>
</body>
</html>
