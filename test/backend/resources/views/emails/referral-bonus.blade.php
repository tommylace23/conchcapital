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
            <h1>Referral Bonus Credited</h1>
        </div>

        <div class="email-body">
            <h2>Dear {{ $user->display_name ?? $user->name }},</h2>

            <p>Your referral has been confirmed and the associated bonus has been credited to your account.</p>

            <div class="highlight-box">
                <p><strong>Bonus credited: ${{ number_format($amount, 2) }} USD</strong></p>
                <p style="margin: 6px 0 0 0;">Referred by: <strong>{{ $referrerName }}</strong></p>
                <p style="margin: 6px 0 0 0;">Reference: <strong>{{ $reference }}</strong></p>
                <p style="margin: 6px 0 0 0;">Real balance now: <strong>${{ number_format($newBalance, 2) }}</strong></p>
            </div>

            <p>The funds are available in your real balance immediately and may be allocated to a Profit Growth
                account or withdrawn in the usual way.</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $appUrl }}/dashboard.html" class="cta-button">View Your Balance</a>
            </div>

            <div class="highlight-box" style="background-color: #F8FAFC;">
                <p><strong>Share your own code</strong></p>
                <p style="margin: 6px 0 0 0; font-size: 13px;">
                    Your referral details are available from your dashboard. Anyone who joins with your code receives
                    a bonus as soon as they register.
                </p>
            </div>
        </div>

        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Private investment management and portfolio growth.</p>
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
