<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PlaySpace</title>
</head>
<body style="margin:0;padding:0;background-color:#0f1117;font-family:'Segoe UI',system-ui,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f1117;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:linear-gradient(135deg,#1a1f2e 0%,#16213e 100%);border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#1D9E75 0%,#16c88a 100%);padding:40px 48px;text-align:center;">
                            <div style="font-size:32px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
                                ⚽ PlaySpace
                            </div>
                            <div style="margin-top:8px;font-size:14px;color:rgba(255,255,255,0.85);letter-spacing:1px;text-transform:uppercase;">
                                Complex Management Platform
                            </div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:48px;">

                            <h1 style="margin:0 0 8px;font-size:26px;font-weight:700;color:#ffffff;line-height:1.3;">
                                Welcome, {{ $gerant->first_name }}! 👋
                            </h1>
                            <p style="margin:0 0 24px;font-size:15px;color:#9ca3af;line-height:1.6;">
                                You've been added as the manager of
                                <strong style="color:#1D9E75;">{{ $complexeName }}</strong>
                                on PlaySpace. Your account is ready to go.
                            </p>

                            {{-- Info card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(29,158,117,0.08);border:1px solid rgba(29,158,117,0.25);border-radius:12px;margin-bottom:32px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <div style="font-size:13px;color:#6b7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Your account email</div>
                                        <div style="font-size:16px;color:#e5e7eb;font-weight:600;">{{ $gerant->email }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 24px 20px;">
                                        <div style="font-size:13px;color:#6b7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Assigned complex</div>
                                        <div style="font-size:16px;color:#e5e7eb;font-weight:600;">{{ $complexeName }}</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px;font-size:15px;color:#9ca3af;line-height:1.6;">
                                To activate your account, you need to set your own password. Click the button below — the link is valid for <strong style="color:#e5e7eb;">60 minutes</strong>.
                            </p>

                            {{-- CTA Button --}}
                            <table cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#1D9E75 0%,#16c88a 100%);border-radius:10px;">
                                        <a href="{{ $resetUrl }}"
                                           style="display:inline-block;padding:15px 36px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;letter-spacing:0.3px;">
                                            Set my password →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Fallback link --}}
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
                                If the button doesn't work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0;font-size:12px;color:#4b5563;word-break:break-all;">
                                {{ $resetUrl }}
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 48px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;">
                            <p style="margin:0;font-size:12px;color:#4b5563;line-height:1.6;">
                                If you didn't expect this email, you can safely ignore it.<br>
                                © {{ date('Y') }} PlaySpace — All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
