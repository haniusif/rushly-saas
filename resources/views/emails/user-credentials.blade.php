@php
    // Prefer https://tenantDomain when we have one; falls back to loginUrl's
    // host for the "workspace URL" chip so the recipient always knows which
    // portal this email belongs to.
    $portalUrl = $tenantDomain ?: $loginUrl;
    $portalHost = parse_url($portalUrl, PHP_URL_HOST) ?: parse_url($loginUrl, PHP_URL_HOST) ?: $brand;
    $showContact = $supportEmail || $supportPhone || $supportAddress;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $password ? __('Your new password') : __('Sign in') }} · {{ $brand }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#111827;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 0;">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(15,23,42,.08);">

            {{-- Header: logo + portal name --}}
            <tr>
                <td style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:22px 28px;color:#e2e8f0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="vertical-align:middle;">
                                @if ($brandLogo)
                                    <img src="{{ $brandLogo }}" alt="{{ $brand }}" height="34"
                                         style="height:34px;width:auto;display:block;border:0;outline:none;background:transparent;">
                                @else
                                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;color:#f8fafc;">{{ $brand }}</div>
                                @endif
                            </td>
                            <td style="vertical-align:middle;text-align:right;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.12em;">
                                {{ $portalName }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Greeting --}}
            <tr>
                <td style="padding:32px 32px 8px 32px;">
                    <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">
                        {{ __('Hi :name,', ['name' => $userName ?: 'there']) }}
                    </div>
                </td>
            </tr>

            <tr>
                <td style="padding:0 32px 20px 32px;font-size:14.5px;line-height:24px;color:#334155;">
                    @if($password)
                        {{ __('An administrator has set a new password for your :brand account. Use the credentials below to sign in, then change your password.', ['brand' => $brand]) }}
                    @else
                        {{ __('Your :brand account is ready. Sign in with the email on file — if you have never signed in before, use the forgot-password link on the sign-in page to set your password.', ['brand' => $brand]) }}
                    @endif
                </td>
            </tr>

            {{-- Credentials box --}}
            <tr>
                <td style="padding:0 32px 8px 32px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                        <tr>
                            <td style="padding:16px 18px;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.1em;font-weight:600;border-bottom:1px solid #e2e8f0;">
                                {{ __('Your sign-in details') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:14px 18px;font-size:14px;line-height:22px;color:#0f172a;">
                                <div style="margin-bottom:6px;">
                                    <span style="display:inline-block;width:88px;color:#64748b;">{{ __('Workspace') }}</span>
                                    <a href="{{ $portalUrl }}" style="color:#2563eb;text-decoration:none;">{{ $portalHost }}</a>
                                </div>
                                <div style="margin-bottom:6px;">
                                    <span style="display:inline-block;width:88px;color:#64748b;">{{ __('Email') }}</span>
                                    <strong>{{ $email }}</strong>
                                </div>
                                @if($password)
                                    <div>
                                        <span style="display:inline-block;width:88px;color:#64748b;">{{ __('Password') }}</span>
                                        <code style="background:#ffffff;border:1px solid #e2e8f0;border-radius:5px;padding:3px 8px;font-family:'SFMono-Regular',Menlo,Consolas,monospace;font-size:13px;">{{ $password }}</code>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- CTA button --}}
            <tr>
                <td align="center" style="padding:22px 32px 4px 32px;">
                    <a href="{{ $loginUrl }}"
                       style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 28px;border-radius:10px;font-size:14.5px;">
                        {{ __('Open the portal') }}
                    </a>
                    <div style="font-size:12px;color:#94a3b8;margin-top:10px;word-break:break-all;">
                        {{ $loginUrl }}
                    </div>
                </td>
            </tr>

            {{-- How to sign in --}}
            <tr>
                <td style="padding:28px 32px 8px 32px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.12em;font-weight:700;margin-bottom:10px;">
                        {{ __('How to sign in') }}
                    </div>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:22px;color:#334155;">
                        <tr>
                            <td style="width:28px;vertical-align:top;padding-top:2px;">
                                <div style="width:22px;height:22px;line-height:22px;border-radius:11px;background:#e0e7ff;color:#3730a3;text-align:center;font-weight:700;font-size:11px;">1</div>
                            </td>
                            <td style="padding:0 0 10px 8px;">
                                {{ __('Open the portal using the button above (or the URL below it).') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="width:28px;vertical-align:top;padding-top:2px;">
                                <div style="width:22px;height:22px;line-height:22px;border-radius:11px;background:#e0e7ff;color:#3730a3;text-align:center;font-weight:700;font-size:11px;">2</div>
                            </td>
                            <td style="padding:0 0 10px 8px;">
                                {{ __('Enter your email :email and') }} <strong>{{ $password ? __('the password shown above') : __('your account password') }}</strong>.
                            </td>
                        </tr>
                        @if(! $password)
                        <tr>
                            <td style="width:28px;vertical-align:top;padding-top:2px;">
                                <div style="width:22px;height:22px;line-height:22px;border-radius:11px;background:#fef3c7;color:#92400e;text-align:center;font-weight:700;font-size:11px;">?</div>
                            </td>
                            <td style="padding:0 0 10px 8px;">
                                {{ __('Do not remember your password? Click') }} <em>{{ __('Forgot password') }}</em> {{ __('on the sign-in page and we will email you a reset link.') }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td style="width:28px;vertical-align:top;padding-top:2px;">
                                <div style="width:22px;height:22px;line-height:22px;border-radius:11px;background:#dcfce7;color:#166534;text-align:center;font-weight:700;font-size:11px;">3</div>
                            </td>
                            <td style="padding:0 0 4px 8px;">
                                @if($password)
                                    {{ __('For your security, change this password after your first sign-in from Profile → Change password.') }}
                                @else
                                    {{ __('Bookmark the portal URL — you will use it every time you sign in.') }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Contact block --}}
            @if ($showContact)
            <tr>
                <td style="padding:24px 32px 8px 32px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;border-radius:10px;">
                        <tr>
                            <td style="padding:16px 18px;">
                                <div style="font-size:11px;color:#334155;text-transform:uppercase;letter-spacing:.12em;font-weight:700;margin-bottom:8px;">
                                    {{ __('Need help?') }}
                                </div>
                                <div style="font-size:13.5px;line-height:22px;color:#0f172a;">
                                    {{ __('Our team is here for you.') }}
                                </div>
                                <div style="font-size:13.5px;line-height:22px;color:#334155;margin-top:8px;">
                                    @if ($supportEmail)
                                        <div>
                                            <span style="display:inline-block;width:72px;color:#64748b;">{{ __('Email') }}</span>
                                            <a href="mailto:{{ $supportEmail }}" style="color:#2563eb;text-decoration:none;">{{ $supportEmail }}</a>
                                        </div>
                                    @endif
                                    @if ($supportPhone)
                                        <div>
                                            <span style="display:inline-block;width:72px;color:#64748b;">{{ __('Phone') }}</span>
                                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $supportPhone) }}" style="color:#2563eb;text-decoration:none;">{{ $supportPhone }}</a>
                                        </div>
                                    @endif
                                    @if ($supportAddress)
                                        <div>
                                            <span style="display:inline-block;width:72px;color:#64748b;vertical-align:top;">{{ __('Address') }}</span>
                                            <span>{{ $supportAddress }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endif

            {{-- Footer --}}
            <tr>
                <td style="padding:24px 32px 28px 32px;border-top:1px solid #e2e8f0;">
                    <div style="font-size:12px;line-height:19px;color:#94a3b8;">
                        {{ __("If you didn't expect this email, contact your workspace administrator or reply to this message.") }}
                    </div>
                    <div style="font-size:11px;line-height:17px;color:#cbd5e0;margin-top:12px;">
                        © {{ date('Y') }} {{ $brand }}. {{ __('All rights reserved.') }}
                    </div>
                </td>
            </tr>

        </table>
    </td></tr>
</table>

</body>
</html>
