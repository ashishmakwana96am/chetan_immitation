<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Inquiry</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Nunito', Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <!-- Header -->
        <tr>
            <td style="background-color: #B4771E; padding: 30px 20px; text-align: center;">
                <img src="{{ asset('website/assets/images/logo.png') }}" alt="Chetan Imitation" style="max-width: 150px; height: auto;">
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 40px 30px;">
                <h1 style="color: #131615; font-size: 24px; font-weight: 600; margin: 0 0 20px;">New Contact Inquiry</h1>
                
                <p style="color: #3D403F; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                    You have received a new contact form submission from the website.
                </p>
                
                <!-- Inquiry Details Table -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #131615; font-weight: 600; width: 150px;">Full Name:</td>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #3D403F;">{{ $inquiry->full_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #131615; font-weight: 600;">Email:</td>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #3D403F;"><a href="mailto:{{ $inquiry->email }}" style="color: #B4771E; text-decoration: none;">{{ $inquiry->email }}</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #131615; font-weight: 600;">Phone:</td>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #3D403F;"><a href="tel:{{ $inquiry->phone }}" style="color: #B4771E; text-decoration: none;">{{ $inquiry->phone }}</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #131615; font-weight: 600;">Subject:</td>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #3D403F;">{{ $inquiry->subject }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #131615; font-weight: 600; vertical-align: top;">Message:</td>
                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e5e5; color: #3D403F; white-space: pre-wrap;">{{ $inquiry->message }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; color: #131615; font-weight: 600;">Submitted At:</td>
                        <td style="padding: 12px 0; color: #3D403F;">{{ $inquiry->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </table>
                
                <div style="margin-top: 30px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border-left: 4px solid #B4771E;">
                    <p style="color: #3D403F; font-size: 14px; margin: 0; line-height: 1.6;">
                        <strong style="color: #131615;">Quick Actions:</strong><br>
                        You can view and manage this inquiry in the admin panel under <strong>Contact Inquiries</strong>.
                    </p>
                </div>
            </td>
        </tr>
        
        <!-- Footer -->
        <tr>
            <td style="background-color: #131615; padding: 20px; text-align: center;">
                <p style="color: #D5D5D5; font-size: 14px; margin: 0;">
                    &copy; {{ date('Y') }} Chetan Imitation. All Rights Reserved.
                </p>
                <p style="color: #D5D5D5; font-size: 12px; margin: 10px 0 0;">
                    Developed by <a href="https://www.risingstarinfotech.com/" target="_blank" style="color: #B4771E;">Rising Star Infotech</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>