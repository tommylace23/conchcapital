<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funds Credited - {{ $appName }}</title>
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        .security-badge {
            background-color: #EFF6FF;
            border: 2px solid #3B82F6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .security-badge i {
            color: #3B82F6;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .security-badge p {
            margin: 5px 0;
            color: #1E40AF;
            font-weight: 600;
            font-size: 14px;
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
        .notification-box {
            background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
            border-left: 4px solid #10B981;
            padding: 25px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .notification-box h3 {
            color: #065F46;
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: 700;
        }
        .notification-box p {
            color: #047857;
            margin: 5px 0;
            font-weight: 500;
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
            font-size: 14px;
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
        .fund-type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fund-type-real {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .fund-type-simulated {
            background-color: #DBEAFE;
            color: #1E40AF;
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
        .divider {
            height: 1px;
            background-color: #E5E7EB;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>💰 Funds Credited</h1>
        </div>
        
        <div class="email-body">
            <h2>Hello {{ $user->display_name ?? $user->name }}!</h2>
            
            <div class="security-badge">
                <div style="font-size: 32px; margin-bottom: 10px;">🔒</div>
                <p style="margin: 0; font-size: 12px; color: #1E40AF;">SECURE NOTIFICATION</p>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #3B82F6;">This is an authenticated email from {{ $appName }}</p>
            </div>
            
            <div class="notification-box">
                <h3>✅ Funds Successfully Credited</h3>
                <p>We've successfully credited funds to your account.</p>
                <p style="margin-top: 15px;">
                    <strong>Fund Type:</strong> 
                    <span class="fund-type-badge fund-type-{{ $fundType === 'real' ? 'real' : 'simulated' }}">
                        {{ $fundType === 'real' ? 'Real Balance' : 'Profit Growth Balance' }}
                    </span>
                </p>
                @if($reason)
                <p style="margin-top: 10px;">
                    <strong>Reason:</strong> {{ $reason }}
                </p>
                @endif
            </div>
            
            <div class="info-box">
                <p>
                    <strong>🔐 Security Notice:</strong> For your security, the credited amount is not displayed in this email. 
                    Please log in to your account to view the exact amount credited.
                </p>
            </div>
            
            <p>To view your updated balance and transaction details, please log in to your account.</p>
            
            <div style="text-align: center;">
                <a href="{{ $appUrl }}/dashboard.html" class="cta-button">View My Account</a>
            </div>
            
            <div class="divider"></div>
            
            <p style="font-size: 14px; color: #9CA3AF; margin-top: 30px;">
                <strong>What's Next?</strong><br>
                • Check your account balance in the dashboard<br>
                • Review the transaction in your activity log<br>
                • Your balance is available for investment allocations and withdrawals
            </p>
            
            <p style="margin-top: 40px; font-size: 14px; color: #9CA3AF;">
                If you did not expect this credit or have any concerns, please contact our support team immediately.
            </p>
        </div>
        
        <div class="email-footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>Private investment management and portfolio growth.</p>
            <p style="margin-top: 20px;">
                <a href="{{ $appUrl }}">Visit Website</a> | 
                <a href="{{ $appUrl }}/dashboard.html">Dashboard</a> | 
                <a href="{{ $appUrl }}/SETTINGS_PAGE.html">Account Settings</a>
            </p>
            <p style="font-size: 12px; color: #9CA3AF; margin-top: 20px;">
                This is an automated notification sent to {{ $user->email }}. 
                If you have questions about this credit, please contact support.
            </p>
            <p style="font-size: 11px; color: #D1D5DB; margin-top: 15px;">
                This email was sent from a verified sender. Never share your account credentials.
            </p>
        </div>
    </div>
</body>
</html>
