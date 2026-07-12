<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Inquiry — Chetan Imitation</title>
</head>
<body style="margin:0;padding:0;font-family:'Nunito',Arial,sans-serif;background-color:#f4f4f4;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td align="center" style="padding:30px 15px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background-color:#131615;padding:28px 30px;text-align:center;">
            <img src="https://royalgujarati.com/chetan-imitation/website/assets/images/logo.png" alt="Chetan Imitation" style="max-width:150px;height:auto;display:inline-block;">
        </td>
    </tr>

    <!-- Gold bar -->
    <tr>
        <td style="background-color:#B4771E;height:5px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    <!-- Body -->
    <tr>
        <td style="padding:40px 35px 30px;">

            <h1 style="color:#131615;font-size:24px;font-weight:700;margin:0 0 8px;">New Contact Inquiry</h1>
            <p style="color:#3D403F;font-size:16px;line-height:1.7;margin:0 0 24px;">
                You have received a new contact form submission from the website.
            </p>

            <!-- Inquiry Details Box -->
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7f7f7;border-radius:6px;border-left:4px solid #B4771E;padding:0;margin-bottom:28px;">
                <tr>
                    <td style="padding:20px 22px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;width:110px;font-weight:600;vertical-align:top;">Full Name</td>
                                <td style="color:#131615;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">{{ $inquiry->full_name }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">Email</td>
                                <td style="color:#131615;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">
                                    <a href="mailto:{{ $inquiry->email }}" style="color:#B4771E;text-decoration:none;">{{ $inquiry->email }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">Phone</td>
                                <td style="color:#131615;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">
                                    <a href="tel:{{ $inquiry->phone }}" style="color:#B4771E;text-decoration:none;">{{ $inquiry->phone }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">Subject</td>
                                <td style="color:#131615;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">{{ $inquiry->subject }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">Message</td>
                                <td style="color:#3D403F;font-size:14px;padding:6px 0;line-height:1.6;vertical-align:top;white-space:pre-wrap;">{{ $inquiry->message }}</td>
                            </tr>
                            <tr>
                                <td style="color:#757575;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">Submitted At</td>
                                <td style="color:#131615;font-size:14px;padding:6px 0;font-weight:600;vertical-align:top;">{{ $inquiry->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div style="padding:16px 20px;background:#fdf9f4;border-radius:6px;border-left:4px solid #B4771E;">
                <p style="color:#555555;font-size:13px;line-height:1.6;margin:0;">
                    <strong>Quick Actions:</strong><br>
                    You can view and manage this inquiry in the admin panel under <strong>Contact Inquiries</strong>.
                </p>
            </div>

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