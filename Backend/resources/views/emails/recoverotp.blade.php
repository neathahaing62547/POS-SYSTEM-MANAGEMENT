<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
</head>

<body style="margin:0; padding:0; background:#eef0f3; font-family:'Segoe UI', Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f3; padding:48px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:480px; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 4px 24px rgba(17,24,39,0.06);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#111827,#1f2937); padding:32px; text-align:center;">
                            <span style="display:inline-block; letter-spacing:2px; font-size:12px; font-weight:600; color:#9ca3af; text-transform:uppercase; margin-bottom:6px;">
                                Secure Login
                            </span>
                            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:0.5px;">
                                POS SYSTEM
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 36px 32px;">

                            <h2 style="margin:0 0 10px; color:#111827; font-size:20px; font-weight:700; text-align:center;">
                                Verify your login
                            </h2>
                            <p style="margin:0 0 28px; color:#6b7280; font-size:14px; line-height:1.6; text-align:center;">
                                We received a request to log in to your account.<br>
                                Enter the code below to continue.
                            </p>

                            <table align="center" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto 24px;">
                                <tr>
                                    @foreach (str_split((string) $otp) as $digit)
                                        <td width="46" height="56" align="center" valign="middle"
                                            style="width:46px; height:56px; background:#f9fafb; border:1px solid #d1d5db; border-radius:10px; font-size:28px; font-weight:700; color:#111827; font-family:'Courier New', monospace; text-align:center;">
                                            {{ $digit }}
                                        </td>
                                        @if (!$loop->last)
                                            <td width="8" style="width:8px; font-size:0; line-height:0;">&nbsp;</td>
                                        @endif
                                    @endforeach
                                </tr>
                            </table>

                            <p style="margin:0; text-align:center; color:#6b7280; font-size:13px;">
                                Expires in <strong style="color:#111827;">5 minutes</strong>
                            </p>

                            <hr style="border:none; border-top:1px solid #f0f1f3; margin:28px 0;">

                            <p style="margin:0; color:#9ca3af; font-size:12.5px; line-height:1.6; text-align:center;">
                                If you didn't request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 36px; background:#f9fafb; border-top:1px solid #eef0f3; text-align:center;">
                            <p style="margin:0 0 4px; color:#9ca3af; font-size:11.5px;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                            <p style="margin:0; color:#c1c5cb; font-size:11px;">
                                POS SYSTEM v1.0 · Developed by Haing Neatha
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>