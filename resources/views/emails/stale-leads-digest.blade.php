<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.stale_leads_email_subject', ['count' => $leads->count()]) }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f59e0b, #d97706); padding: 24px; color: #fff; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; opacity: 0.9; font-size: 14px; }
        .content { padding: 24px; }
        .lead-card { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; }
        .lead-card h3 { margin: 0 0 4px; font-size: 15px; color: #92400e; }
        .lead-card p { margin: 0; font-size: 13px; color: #78716c; }
        .lead-card .idle { color: #dc2626; font-weight: 600; font-size: 12px; margin-top: 4px; }
        .footer { padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 {{ __('messages.stale_leads_email_heading') }}</h1>
            <p>{{ __('messages.stale_leads_email_intro', ['name' => $recipientName]) }}</p>
        </div>
        <div class="content">
            <p style="margin-bottom: 16px; font-size: 14px;">
                {{ __('messages.stale_leads_email_body', ['count' => $leads->count()]) }}
            </p>
            @foreach($leads as $lead)
                <div class="lead-card">
                    <h3>{{ $lead->name }}</h3>
                    <p>{{ $lead->company ?? $lead->code }} &middot; {{ $lead->email ?? '—' }}</p>
                    <p class="idle">
                        ⏱ {{ __('messages.idle_for_days', ['days' => (int) $lead->updated_at->diffInDays(now())]) }}
                        &mdash; {{ __('messages.last_updated') }}: {{ $lead->updated_at->format('M d, Y') }}
                    </p>
                </div>
            @endforeach
        </div>
        <div class="footer">
            {{ config('app.name') }} &middot; {{ now()->format('M d, Y H:i') }}
        </div>
    </div>
</body>
</html>
