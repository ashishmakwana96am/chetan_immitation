<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#B4771E;padding:28px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:1px;">
                                Chetan Imitation
                            </h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 40px 30px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#333333;">
                                Hello <strong>{{ $customerName }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;font-size:16px;color:#555555;line-height:1.6;">
                                We received a request to reset the password for your Chetan Imitation account.
                                Use the OTP below to proceed. This code is valid for <strong>10 minutes</strong>.
                            </p>
                            <!-- OTP Box -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:10px 0 30px;">
                                        <div style="display:inline-block;background:#f9f3e8;border:2px dashed #B4771E;border-radius:8px;padding:20px 48px;">
                                            <span style="font-size:42px;font-weight:700;letter-spacing:12px;color:#B4771E;">{{ $otp }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px;font-size:14px;color:#888888;line-height:1.6;">
                                If you did not request a password reset, please ignore this email. Your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9f9f9;padding:20px 40px;text-align:center;border-top:1px solid #eeeeee;">
                            <p style="margin:0;font-size:13px;color:#aaaaaa;">
                                &copy; {{ date('Y') }} Chetan Imitation. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
