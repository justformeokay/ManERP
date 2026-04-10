<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $isEscalation ? __('messages.email_lead_escalation_subject', ['name' => $client->name]) : __('messages.email_lead_reminder_subject', ['name' => $client->name]) }}</title>
    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f3f4f6; color: #374151; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6;">

    <!-- Outer wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 16px;">
    <tr><td align="center">
    <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.08);">

        <!-- ═══ HEADER ═══ -->
        <tr>
            <td style="background: linear-gradient(135deg,{{ $isEscalation ? '#dc2626,#b91c1c' : '#f59e0b,#d97706' }}); padding:28px 32px;">
                <!-- ManERP Text Logo -->
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="background:#ffffff; border-radius:8px; padding:6px 12px;">
                            <span style="font-size:18px; font-weight:800; color:{{ $isEscalation ? '#dc2626' : '#d97706' }}; letter-spacing:-0.5px;">Man</span><span style="font-size:18px; font-weight:800; color:#1f2937; letter-spacing:-0.5px;">ERP</span>
                        </td>
                    </tr>
                </table>
                <h1 style="margin:16px 0 4px; font-size:20px; font-weight:700; color:#ffffff;">
                    {{ $isEscalation ? '🚨' : '🔔' }}
                    {{ $isEscalation ? __('messages.email_lead_escalation_heading') : __('messages.email_lead_reminder_heading') }}
                </h1>
                <p style="margin:0; font-size:14px; color:rgba(255,255,255,0.9);">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </td>
        </tr>

        <!-- ═══ GREETING ═══ -->
        <tr>
            <td style="padding:28px 32px 0;">
                <p style="margin:0 0 16px; font-size:15px; color:#374151;">
                    {{ __('messages.email_lead_greeting', ['name' => $salesName]) }}
                </p>
                <p style="margin:0 0 20px; font-size:14px; color:#6b7280;">
                    {{ $isEscalation
                        ? __('messages.email_lead_escalation_intro', ['days' => $idleDays])
                        : __('messages.email_lead_reminder_intro', ['days' => $idleDays])
                    }}
                </p>
            </td>
        </tr>

        <!-- ═══ LEAD DETAIL CARD ═══ -->
        <tr>
            <td style="padding:0 32px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $isEscalation ? '#fef2f2' : '#fffbeb' }}; border:1px solid {{ $isEscalation ? '#fecaca' : '#fde68a' }}; border-radius:10px; overflow:hidden;">
                    <!-- Urgency bar -->
                    <tr>
                        <td style="background:{{ $isEscalation ? '#dc2626' : '#f59e0b' }}; height:4px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px;">
                            <!-- Client name -->
                            <p style="margin:0 0 4px; font-size:17px; font-weight:700; color:{{ $isEscalation ? '#991b1b' : '#92400e' }};">
                                {{ $client->name }}
                            </p>
                            <!-- Company / Code -->
                            <p style="margin:0 0 12px; font-size:13px; color:#6b7280;">
                                {{ $client->company ?? $client->code }}
                                @if($client->email)
                                    &middot; {{ $client->email }}
                                @endif
                            </p>
                            <!-- Stats row -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="50%" style="padding:8px 12px; background:#ffffff; border-radius:8px; border:1px solid {{ $isEscalation ? '#fecaca' : '#fde68a' }};">
                                        <p style="margin:0; font-size:11px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">
                                            {{ __('messages.email_lead_idle_days') }}
                                        </p>
                                        <p style="margin:4px 0 0; font-size:22px; font-weight:800; color:{{ $isEscalation ? '#dc2626' : '#d97706' }};">
                                            {{ $idleDays }}
                                            <span style="font-size:12px; font-weight:500; color:#9ca3af;">{{ __('messages.stab_days') }}</span>
                                        </p>
                                    </td>
                                    <td width="8" style="font-size:0;">&nbsp;</td>
                                    <td width="50%" style="padding:8px 12px; background:#ffffff; border-radius:8px; border:1px solid {{ $isEscalation ? '#fecaca' : '#fde68a' }};">
                                        <p style="margin:0; font-size:11px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">
                                            {{ __('messages.email_lead_last_activity') }}
                                        </p>
                                        <p style="margin:4px 0 0; font-size:14px; font-weight:600; color:#374151;">
                                            {{ $client->updated_at->translatedFormat('d M Y') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- ═══ CTA BUTTON ═══ -->
        <tr>
            <td style="padding:24px 32px;" align="center">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="background:{{ $isEscalation ? '#dc2626' : '#f59e0b' }}; border-radius:8px; padding:12px 28px;">
                            <a href="{{ url('/clients/' . $client->id) }}" style="color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; display:inline-block;">
                                {{ __('messages.email_lead_cta') }}
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin:12px 0 0; font-size:12px; color:#9ca3af;">
                    {{ __('messages.email_lead_cta_hint') }}
                </p>
            </td>
        </tr>

        <!-- ═══ TIPS ═══ -->
        <tr>
            <td style="padding:0 32px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:16px 20px;">
                            <p style="margin:0 0 8px; font-size:13px; font-weight:700; color:#374151;">
                                💡 {{ __('messages.email_lead_tips_title') }}
                            </p>
                            <ul style="margin:0; padding-left:18px; font-size:13px; color:#6b7280;">
                                <li style="margin-bottom:4px;">{{ __('messages.email_lead_tip_1') }}</li>
                                <li style="margin-bottom:4px;">{{ __('messages.email_lead_tip_2') }}</li>
                                <li>{{ __('messages.email_lead_tip_3') }}</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- ═══ FOOTER ═══ -->
        <tr>
            <td style="padding:20px 32px; background:#f9fafb; border-top:1px solid #e5e7eb;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                {{ __('messages.email_lead_footer', ['app' => config('app.name')]) }}
                            </p>
                        </td>
                        <td align="right">
                            <span style="font-size:12px; font-weight:700; color:#d1d5db;">Man</span><span style="font-size:12px; font-weight:700; color:#9ca3af;">ERP</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
    </td></tr>
    </table>

</body>
</html>
