<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('auth.login_otp_subject') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,.06);">
                    <tr>
                        <td style="font-size:14px;color:#6b7280;padding-bottom:8px;">
                            {{ settings()?->company_name ?? config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:20px;font-weight:600;color:#111827;padding-bottom:16px;">
                            {{ __('auth.login_otp_greeting', ['name' => $userName]) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:22px;color:#374151;padding-bottom:24px;">
                            {{ __('auth.login_otp_intro') }}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:16px 0 24px 0;">
                            <div style="display:inline-block;font-size:32px;letter-spacing:8px;font-weight:700;color:#111827;background:#f3f4f6;border-radius:8px;padding:16px 24px;">
                                {{ $code }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;line-height:20px;color:#6b7280;padding-bottom:16px;">
                            {{ __('auth.login_otp_expiry', ['minutes' => $expiresInMinutes]) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:18px;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:16px;">
                            {{ __('auth.login_otp_ignore') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
