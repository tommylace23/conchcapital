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
                <p><strong>How to fund your account</strong></p>
                <p style="margin: 8px 0 6px 0;">
                    Bank transfer (USD)<br>
                    Beneficiary: <strong>{{ $bank['bank_account_holder'] }}</strong><br>
                    Bank: <strong>{{ $bank['bank_name'] }}</strong><br>
                    SWIFT / BIC: <strong>{{ $bank['bank_swift'] }}</strong><br>
                    Routing Number: <strong>{{ $bank['bank_routing'] }}</strong><br>
                    Bank Address: {{ $bank['bank_address'] }}<br>
                    Your Deposit Reference: <strong>{{ $depositReference }}</strong>
                </p>
                <p style="margin: 6px 0 0 0; font-size: 13px;">
                    Always include your deposit reference so the transfer is matched to your profile.
                    Digital assets (BTC, ETH, USDT) are also accepted from the Deposit page.
                </p>
            </div>

            <p>You may now proceed with the following:</p>

            <ul class="features-list">
                <li>Fund your account by bank transfer or digital asset deposit</li>
                <li>Allocate capital to a Profit Growth account</li>
                <li>Transfer funds to other clients on the platform</li>
                <li>Request withdrawals at any time, subject to settlement terms</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ $appUrl }}/dashboard.html" class="cta-button">Access Your Dashboard</a>
            </div>

            <p style="margin-top: 40px; font-size: 14px; color: #9CA3AF;">
                Should you require assistance, our client services team is available through your dashboard.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Private investment management and portfolio growth.</p>
            <p style="margin-top: 20px;">
                <a href="{{ $appUrl }}">Visit Website</a> | 
                <a href="{{ $appUrl }}/SETTINGS_PAGE.html">Account Settings</a>
            </p>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 20px;">
                This email was sent to {{ $user->email }}. If you didn't create an account, please ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
