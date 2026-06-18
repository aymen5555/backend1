<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify your email</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <h2>Welcome to PlaySpace, {{ $user->first_name }}!</h2>
    <p>Please confirm your email address to activate your {{ $user->isGerantOrAdmin() ? 'complex owner' : 'player' }} account.</p>
    <p>
        <a href="{{ $verifyUrl }}"
           style="display:inline-block;padding:12px 24px;background:#1D9E75;color:#fff;text-decoration:none;border-radius:8px;">
            Verify email address
        </a>
    </p>
    <p style="font-size:13px;color:#666;">
        This link expires in 24 hours. If you did not create an account, you can ignore this email.
    </p>
    <p style="font-size:12px;color:#999;word-break:break-all;">{{ $verifyUrl }}</p>
</body>
</html>
