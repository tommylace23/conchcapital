<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile View Request - {{ $appName }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h2 {
            color: #111827;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .email-body p {
            color: #6B7280;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .request-box {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .request-box p {
            margin: 0;
            color: #1E40AF;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background-color: #10B981;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px 5px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #059669;
        }
        .cta-button-secondary {
            background-color: #EF4444;
        }
        .cta-button-secondary:hover {
            background-color: #DC2626;
        }
        .email-footer {
            background-color: #F9FAFB;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }
        .email-footer p {
            color: #6B7280;
            font-size: 14px;
            margin: 5px 0;
        }
        .email-footer a {
            color: #3B82F6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Profile View Request</h1>
        </div>
        
        <div class="email-body">
            <h2>Hello {{ $profileOwner->display_name ?? $profileOwner->name }}!</h2>
            
            <p><strong>{{ $requester->display_name ?? $requester->name }}</strong> ({{ '@' . $requester->username }}) has requested to view your portfolio and profile on {{ $appName }}.</p>
            
            <div class="request-box">
                <p><strong>📋 Request Details</strong><br>
                User: {{ $requester->display_name ?? $requester->name }} ({{ '@' . $requester->username }})<br>
                Requested at: {{ now()->format('F j, Y \a\t g:i A') }}</p>
            </div>
            
            <p>You can approve or decline this request. If approved, they will be able to view your portfolio statistics and profile information.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $appUrl }}/notification-page.html" class="cta-button">Review Request</a>
            </div>
            
            <p style="margin-top: 40px; font-size: 14px; color: #9CA3AF;">
                You can manage all profile view requests from your notifications page.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Private investment management and portfolio growth.</p>
            <p style="margin-top: 20px;">
                <a href="{{ $appUrl }}">Visit Website</a> | 
                <a href="{{ $appUrl }}/notification-page.html">Notifications</a>
            </p>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 20px;">
                This email was sent to {{ $profileOwner->email }}. If you didn't expect this request, please ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
