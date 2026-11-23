<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile View Request Declined - {{ $appName }}</title>
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
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
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
        .info-box {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .info-box p {
            margin: 0;
            color: #92400E;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background-color: #3B82F6;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 30px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2563EB;
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
            <h1>Request Declined</h1>
        </div>
        
        <div class="email-body">
            <h2>Hello {{ $requester->display_name ?? $requester->name }},</h2>
            
            <p>We wanted to let you know that <strong>{{ $profileOwner->display_name ?? $profileOwner->name }}</strong> has declined your request to view their portfolio and profile.</p>
            
            <div class="info-box">
                <p><strong>ℹ️ Privacy Preference</strong><br>
                {{ $profileOwner->display_name ?? $profileOwner->name }} has chosen to keep their profile private at this time. You can still send them funds or interact with them in other ways on the platform.</p>
            </div>
            
            <p>You can continue to explore other users' profiles or send funds to {{ $profileOwner->display_name ?? $profileOwner->name }} if they accept transfers.</p>
            
            <div style="text-align: center;">
                <a href="{{ $appUrl }}/profile-search.html" class="cta-button">Explore Other Profiles</a>
            </div>
            
            <p style="margin-top: 40px; font-size: 14px; color: #9CA3AF;">
                Thank you for understanding and respecting privacy preferences on {{ $appName }}.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Private investment management and portfolio growth.</p>
            <p style="margin-top: 20px;">
                <a href="{{ $appUrl }}">Visit Website</a> | 
                <a href="{{ $appUrl }}/dashboard.html">Dashboard</a>
            </p>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 20px;">
                This email was sent to {{ $requester->email }}.
            </p>
        </div>
    </div>
</body>
</html>
