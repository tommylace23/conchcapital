<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
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
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
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
        .highlight-box {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .highlight-box p {
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
            margin: 30px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #059669;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        .features-list li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            color: #374151;
        }
        .features-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10B981;
            font-weight: bold;
            font-size: 18px;
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
            color: #10B981;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Welcome to {{ $appName }}!</h1>
        </div>
        
        <div class="email-body">
            <h2>Dear {{ $user->display_name ?? $user->name }},</h2>

            <p>Welcome to {{ $appName }}. Your client account has been successfully opened and is ready for use.</p>

            <div class="highlight-box">
                <p><strong>Your Client Reference: {{ $depositReference }}</strong></p>
                <p style="margin: 6px 0 0 0;">Sign in with: <strong>{{ $user->email }}</strong></p>
                <p style="margin: 6px 0 0 0; font-size: 13px;">Quote your client reference in any correspondence with us.</p>
            </div>

            <p>To get started:</p>

            <ol class="features-list" style="list-style: decimal; padding-left: 20px;">
                <li>Sign in to your dashboard using the email address above</li>
                <li>Open <strong>Deposit</strong> and use the banking details shown in your profile to fund your account</li>
                <li>Allocate capital to a Profit Growth account once your funds have been credited</li>
            </ol>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $appUrl }}/dashboard.html" class="cta-button">Access Your Dashboard</a>
            </div>

            <div class="highlight-box" style="background-color: #F8FAFC;">
                <p><strong>Security</strong></p>
                <p style="margin: 6px 0 0 0; font-size: 13px;">
                    {{ $appName }} will never ask for your password by email. If you did not open this account,
                    please contact us through your dashboard.
                </p>
            </div>

            <p style="margin-top: 28px; font-size: 13px; color: #6B7280;">
                Your capital is at risk — the value of investments may fall as well as rise. Please read our
                <a href="{{ $appUrl }}/risk-disclosure.html" style="color:#10B981;">Risk Disclosure</a>,
                <a href="{{ $appUrl }}/fee-schedule.html" style="color:#10B981;">Fee Schedule</a> and
                <a href="{{ $appUrl }}/terms-of-service.html" style="color:#10B981;">Terms of Service</a>.
            </p>

        </div>
        
        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>{{ $bank['bank_address'] }}</p>
            <p style="margin-top: 20px;">
                <a href="{{ $appUrl }}">Visit Website</a> |
                <a href="{{ $appUrl }}/SETTINGS_PAGE.html">Account Settings</a>
            </p>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 20px;">
                This email was sent to {{ $user->email }}.
            </p>
        </div>
    </div>
</body>
</html>
