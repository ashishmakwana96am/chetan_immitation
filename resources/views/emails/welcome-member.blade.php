<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Chetan Imitation</title>
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

            <h1 style="color:#131615;font-size:26px;font-weight:700;margin:0 0 8px;">Welcome, {{ $customer->name }}! 🎉</h1>
            <p style="color:#3D403F;font-size:16px;line-height:1.7;margin:0 0 24px;">
                Thank you for creating your account with <strong>Chetan Imitation</strong>. We're thrilled to have you as a member of our jewelry family!
            </p>

            <!-- Details box -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7f7f7;border-radius:6px;border-left:4px solid #B4771E;padding:0;margin-bottom:28px;">
                <tr>
                    <td style="padding:20px 22px;">
                        <p style="margin:0 0 10px;color:#131615;font-size:15px;font-weight:600;">Your Account Details</p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;width:110px;">Name</td>
                                <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:600;">{{ $customer->name }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;">Email</td>
                                <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:600;">{{ $customer->email }}</td>
                            </tr>
                            @if($customer->phone)
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:4px 0;">Phone</td>
                                <td style="color:#131615;font-size:14px;padding:4px 0;font-weight:600;">{{ $customer->phone }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>

            <p style="color:#3D403F;font-size:15px;line-height:1.7;margin:0 0 28px;">
                You can now log in to your account to save your favorite products, track orders, and enjoy a seamless shopping experience.
            </p>

            <!-- CTA Button -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 28px;">
                <tr>
                    <td style="background-color:#B4771E;border-radius:4px;">
                        <a href="{{ url('/login') }}" style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;letter-spacing:0.3px;">
                            Login to Your Account
                        </a>
                    </td>
                </tr>
            </table>

            <p style="color:#757575;font-size:14px;line-height:1.7;margin:0;">
                If you did not create this account, please ignore this email or contact us at
                <a href="mailto:info@chetanimitation.com" style="color:#B4771E;text-decoration:none;">info@chetanimitation.com</a>.
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
