<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $password ? 'Your new password' : 'Sign in' }} · {{ $brand }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,.06);">
                    <tr><td style="font-size:14px;color:#6b7280;padding-bottom:8px;">{{ $brand }}</td></tr>
                    <tr>
                        <td style="font-size:20px;font-weight:600;color:#111827;padding-bottom:16px;">
                            {{ __('Hi :name,', ['name' => $userName ?: 'there']) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:22px;color:#374151;padding-bottom:16px;">
                            @if($password)
                                {{ __("An administrator has set a new password for your account. Use the credentials below to sign in.") }}
                            @else
                                {{ __("You can sign in to :brand with the account below.", ['brand' => $brand]) }}
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;font-size:14px;line-height:22px;color:#111827;">
                            <div><strong>{{ __('Email:') }}</strong> {{ $email }}</div>
                            @if($password)
                                <div style="margin-top:6px;"><strong>{{ __('Password:') }}</strong>
                                    <code style="background:#fff;border:1px solid #e5e7eb;border-radius:4px;padding:2px 6px;font-family:monospace;">{{ $password }}</code>
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 0 8px 0;">
                            <a href="{{ $loginUrl }}"
                               style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;font-weight:600;padding:12px 24px;border-radius:8px;font-size:14px;">
                                {{ __('Sign in') }}
                            </a>
                        </td>
                    </tr>

                    @if($password)
                    <tr>
                        <td style="font-size:12px;line-height:20px;color:#9ca3af;padding-top:8px;">
                            {{ __("For your security, please change this password after your first sign-in.") }}
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="font-size:12px;line-height:18px;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:16px;">
                            {{ __("If you didn't expect this email, contact your workspace administrator.") }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
