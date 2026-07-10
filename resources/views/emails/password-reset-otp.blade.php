<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP — Chetan Imitation</title>
</head>
<body style="margin:0;padding:0;font-family:'Nunito',Arial,sans-serif;background-color:#f4f4f4;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td align="center" style="padding:30px 15px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background-color:#131615;padding:28px 30px;text-align:center;">
            <img src="{{ asset('website/assets/images/logo.png') }}" alt="Chetan Imitation" style="max-width:150px;height:auto;display:inline-block;">
        </td>
    </tr>

    <!-- Gold bar -->
    <tr>
        <td style="background-color:#B4771E;height:5px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    <!-- Body -->
    <tr>
        <td style="padding:40px 35px 30px;">

            <h1 style="color:#131615;font-size:24px;font-weight:700;margin:0 0 16px;">Password Reset Request</h1>
            <p style="color:#3D403F;font-size:16px;line-height:1.7;margin:0 0 24px;">
                Hello <strong>{{ $customerName }}</strong>,
            </p>
            <p style="color:#3D403F;font-size:16px;line-height:1.7;margin:0 0 28px;">
                We received a request to reset the password for your Chetan Imitation account. Use the OTP below to proceed. This code is valid for <strong>10 minutes</strong>.
            </p>

            <!-- OTP Box -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 28px; text-align:center;">
                <tr>
                    <td align="center">
                        <div style="display:inline-block;background:#fdf9f4;border:2px dashed #B4771E;border-radius:8px;padding:20px 48px;">
                            <span style="font-size:42px;font-weight:700;letter-spacing:12px;color:#B4771E;font-family:'Courier New', Courier, monospace;">{{ $otp }}</span>
                        </div>
                    </td>
                </tr>
            </table>

            <p style="color:#757575;font-size:14px;line-height:1.7;margin:0;">
                If you did not request a password reset, please ignore this email. Your password will remain unchanged.
            </p>

        </td>
    </tr>

    <!-- Divider -->
    <tr>
        <td style="padding:0 35px;"><hr style="border:none;border-top:1px solid #e8e8e8;margin:0;"></td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background-color:#131615;padding:22px 30px;text-align:center;">
            <p style="color:#D5D5D5;font-size:13px;margin:0 0 6px;">
                &copy; {{ date('Y') }} Chetan Imitation. All Rights Reserved.
            </p>
            <p style="color:#757575;font-size:12px;margin:0;">
                Developed by <a href="https://risingstarinfotech.com/" target="_blank" style="color:#B4771E;text-decoration:none;">Rising Star Infotech</a>
            </p>
        </td>
    </tr>

</table>
</td>
</tr>
</table>
</body>
</html>
