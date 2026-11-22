# ConchCapital - Complete Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture & Tech Stack](#architecture--tech-stack)
3. [Core Features](#core-features)
4. [Database Schema](#database-schema)
5. [API Documentation](#api-documentation)
6. [Profit Growth System](#profit-growth-system)
7. [User Roles & Permissions](#user-roles--permissions)
8. [Frontend Structure](#frontend-structure)
9. [Deployment & Server Setup](#deployment--server-setup)
10. [Security Features](#security-features)
11. [File Structure](#file-structure)
12. [Configuration](#configuration)
13. [Development Guide](#development-guide)

---

## Project Overview

**ConchCapital** is a professional investment platform that provides users with a dual-environment system for managing both real cryptocurrency funds and profit growth accounts. The platform emphasizes transparency, security, and education, allowing users to:

- **Manage Real Funds**: Deposit, track, and withdraw actual cryptocurrency with clear visual indicators
- **Profit Growth Accounts**: Automatically grow funds at 2.8% daily (20% weekly) through intelligent compounding
- **Network & Collaboration**: Connect with other investors, view profiles, and share strategies
- **Educational Focus**: Clear distinction between real funds and profit growth funds for learning purposes

### Key Differentiators
- **Visual Distinction**: Real funds use green borders/solid lines, profit growth uses blue/dashed borders
- **Automatic Growth**: Profit growth accounts compound daily without manual intervention
- **Security First**: Real funds are protected and separate from growth activities
- **Professional Network**: Peer-to-peer collaboration and profile viewing system

---

## Architecture & Tech Stack

### Backend
- **Framework**: Laravel 12.0 (PHP 8.2+)
- **Authentication**: Laravel Sanctum (Session-based)
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)
- **Queue System**: Laravel Queue (for background jobs)
- **Email**: Laravel Mail (SMTP)
- **Scheduled Tasks**: Laravel Scheduler (Cron)

### Frontend
- **Framework**: Vanilla JavaScript (no framework)
- **Styling**: Tailwind CSS (via CDN)
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Inter (sans-serif), JetBrains Mono (monospace)
- **Routing**: Custom JavaScript routing system

### Server
- **Web Server**: Apache/Nginx
- **PHP**: 8.2+
- **Node.js**: For asset compilation (optional)

---

## Core Features

### 1. User Authentication & Management
- Email/password registration and login
- Email verification system
- Password reset functionality
- Two-factor authentication (2FA) support
- Session management
- Account deletion

### 2. Real Wallet System
- Deposit real cryptocurrency
- Withdraw funds to external wallets
- Real-time balance tracking
- Transaction history
- Clear visual indicators (green borders, solid lines)

### 3. Profit Growth System
- **Automatic Growth**: 2.8% daily, 20% weekly
- **Lock Period**: 7-day lock after each deposit
- **Weekly Limit**: One deposit per week
- **Compound Interest**: Daily compounding on full balance
- **Visual Distinction**: Blue/dashed borders
- **Admin Management**: Admins can create and manage profit growth accounts

### 4. Simulated Transfers
- Send simulated funds to other users
- Receive simulated funds
- Track sent and received transfers
- Email notifications on receipt

### 4a. Referral Program
- Every user is auto-assigned a unique `referral_code` on creation
- Invite link: `/registration.html?ref=CODE` (code is prefilled on the signup form)
- A new user who joins with a valid code receives a bonus **credited to their Real Balance**
- Bonus amount resolution: per-referrer override (`users.referral_bonus_amount`) → falls back to the platform default (`REFERRAL_DEFAULT_BONUS`, `config/referral.php`)
- Credits are recorded as a `transactions` row (`type=credit`, `fund_type=real`, `status=completed`) plus an `activity_logs` entry
- `referrals` table records referrer, referee, code, amount and status (`credited` / `pending` when bonus is 0)
- API: `GET /api/referrals` (own code, invite link, stats) · `POST /api/admin/users/{id}/referral-bonus` (admin sets the bonus)
- Dashboard shows a referral card (code, invite link, counts); registration shows the referral field and bonus confirmation

### 4b. Public Payouts Feed & Growth Projection
- `GET /api/activity/public` — unauthenticated feed of recent **completed withdrawals**, privacy-masked ("Marcus T."), no ids/emails
- `js/payouts.js` + `#payoutsTicker` renders a scrolling "Recent Payouts" marquee on `home.html` and `dashboard.html` (auto-hides when empty)
- Dashboard "Your Growth Potential" card projects 30/90-day compounding (2.8%/day) from the user's profit-growth balance (falls back to real balance, then a $100 example)

### 4c. Client Banking (accounts, references, disclosures)
- Every client is auto-issued a unique 12-digit `account_number` and a valid mod-97 `account_iban` (KY) on signup (`App\Support\BankAccount`)
- `GET /api/bank-details` returns the client's account, a unique deposit reference (`DEP-<account_number>`), settlement bank details and their relationship manager
- `bank-details.html` — client-facing page (account number, IBAN, deposit instructions, copy buttons, RM/support)
- `deposit.html` shows the client's deposit reference plus a link to the bank-details page
- Admin deposit queue shows the transaction/bank reference to allow matching
- Bank/RM values come from the `platform_settings` table, seeded from `config/banking.php` and fully editable by admins at `admin-bank-settings.html` (`GET/PUT /api/admin/bank-settings`, `POST .../reset`); stored overrides take priority, clearing one restores the config default
- Static compliance pages: `risk-disclosure.html`, `fee-schedule.html` (linked from the home footer)
- Dashboard shows a portfolio allocation donut and a relationship-manager card

### 5. Profile System
- User profiles with bio, avatar, display name
- Profile view requests (privacy control)
- Public portfolio viewing
- User search functionality

### 6. Admin Dashboard
- User management (view, freeze, unfreeze, delete)
- Credit/debit funds — `POST /api/admin/credits` takes `direction` (`credit`|`debit`); debits are balance-checked against the selected wallet and recorded as `type=debit`
- Withdrawals apply the configured platform fee (`banking.withdrawal_fee_percent`, default 1.5%, `BANK_WITHDRAWAL_FEE_PERCENT`) with `fee_amount`/`net_amount` stored in transaction metadata
- Client-supplied strings are escaped with `Utils.escapeHtml()` before insertion into admin `innerHTML`
- All admin pages are gated client-side by `guardAdminPage()` in `js/main.js` (server checks remain authoritative)
- Auth endpoints are rate limited: login/register `10/min`, 2FA verify `15/min`, password reset `5/min`
- Frozen accounts are blocked from credits/debits and withdrawals; deleting a user keeps its `admin_actions` rows (target set to null)
- `admin/users` supports `sort` + `direction`; transactions have a detail modal
- Bulk freeze/unfreeze (`POST /api/admin/users/bulk`) with checkbox selection in the user list
- **Single-admin mode**: one operator. `POST /api/admin/admins` refuses a second admin (422); the settings page shows the current admin read-only and only offers creation when none exists
- `users.last_login_at` / `last_login_ip` recorded on login and shown on the admin user page (`security` block)
- Reports page has a live summary driven by `getReports` date range and exports via the compliance endpoint
- **Silent impersonation**: `POST /api/admin/users/{id}/impersonate` swaps the session to the client; `POST /api/impersonation/stop` returns; `GET /api/impersonation/status` drives a fixed banner. The client is not notified — no `activity_logs` row and their `last_login_*` stay unchanged; only `admin_actions` is written
- Transaction management
- Ops pages backed by real endpoints: `GET /api/admin/actions-log`, `GET /api/admin/simulated-transfers`, `GET /api/admin/frozen-accounts`, `GET /api/admin/compliance-export?start_date&end_date&format=csv|json`, `GET|PUT /api/admin/platform-settings`
- Admin-scoped profit growth reads: `GET /api/admin/users/{id}/profit-growths`, `GET /api/admin/profit-growths/{id}`
- Profit growth management
- Deposit/withdrawal confirmation
- Reports and analytics
- Compliance exports

### 7. Activity Logging
- Comprehensive activity tracking
- User actions logged
- Admin actions logged
- Transaction history
- Audit trail

### 8. Notifications
- Email notifications
- In-app notifications
- Activity feed
- Notification preferences

### 9. Settings & Preferences
- Account settings
- Notification preferences
- Security settings (2FA, sessions)
- Password/email change
- Account deletion

### 10. Landing Page Features
- Interactive profit growth calculator
- FAQ section
- Feature explanations
- How it works guide
- Newsletter subscription

---

## Database Schema

### Core Tables

#### `users`
- `id`, `name`, `email`, `username`, `password`
- `real_balance`, `simulated_balance`, `balance_currency`
- `is_admin`, `is_frozen`, `frozen_at`, `freeze_reason`
- `email_verified_at`
- `display_name`, `bio`, `avatar_url`
- `two_factor_enabled`, `two_factor_secret`
- `email_notifications_enabled`, `push_notifications_enabled`
- `last_active_at`, `created_at`, `updated_at`

#### `profit_growths`
- `id`, `user_id`, `name`, `description`
- `initial_balance`, `current_balance`, `currency`
- `status` (active, paused, completed, cancelled)
- `growth_percentage` (default: 20.0)
- `growth_frequency` (daily, weekly, monthly, quarterly, yearly, test)
- `last_growth_calculated_at`, `next_growth_calculation_at`
- `auto_calculate` (boolean)
- `locked_amount`, `lock_until_date`
- `last_transfer_date`
- `started_at`, `completed_at`
- `strategy_data` (JSON)
- `created_at`, `updated_at`

#### `transactions`
- `id`, `user_id`, `type` (deposit, withdrawal, credit, debit)
- `fund_type` (real, simulated)
- `amount`, `currency`
- `status` (pending, completed, failed, cancelled)
- `description`, `reference`
- `metadata` (JSON)
- `processed_at`, `created_at`, `updated_at`

#### `simulated_transfers`
- `id`, `from_user_id`, `to_user_id`
- `simulation_id` (profit_growth_id)
- `amount`, `currency`
- `status`, `description`
- `created_at`, `updated_at`

#### `activity_logs`
- `id`, `user_id`, `action`
- `fund_type`, `entity_type`, `entity_id`
- `amount`, `currency`, `description`
- `created_at`

#### `admin_actions`
- `id`, `admin_id`, `action_type`
- `target_user_id`, `entity_type`, `entity_id`
- `description`, `metadata` (JSON)
- `created_at`

#### `profile_view_requests`
- `id`, `requester_id`, `profile_owner_id`
- `status` (pending, approved, declined)
- `created_at`, `updated_at`

#### `notifications`
- `id`, `user_id`, `type`, `title`, `message`
- `data` (JSON), `read_at`
- `created_at`, `updated_at`

#### `newsletter_subscriptions`
- `id`, `email`, `status` (subscribed, unsubscribed)
- `subscribed_at`, `unsubscribed_at`
- `created_at`, `updated_at`

---

## API Documentation

### Base URL
```
/api
```

### Authentication
All protected routes require authentication via Laravel Sanctum session cookies.

### Public Endpoints

#### Newsletter
- `POST /api/newsletter/subscribe` - Subscribe to newsletter
- `POST /api/newsletter/unsubscribe` - Unsubscribe from newsletter

#### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/forgot-password` - Request password reset
- `POST /api/auth/reset-password` - Reset password
- `POST /api/auth/verify-2fa-login` - Verify 2FA code

### Authenticated Endpoints

#### User Profile
- `GET /api/user/profile` - Get current user profile
- `PUT /api/user/profile` - Update profile
- `GET /api/user/search` - Search users
- `GET /api/user/profile/{id}` - Get user profile
- `GET /api/user/profile/{id}/portfolio` - Get user portfolio

#### Settings
- `GET /api/user/settings` - Get settings
- `PUT /api/user/settings/account` - Update account settings
- `PUT /api/user/settings/notifications` - Update notification preferences
- `POST /api/user/settings/change-password` - Change password
- `POST /api/user/settings/change-email` - Change email
- `GET /api/user/settings/sessions` - Get active sessions
- `DELETE /api/user/settings/sessions/{id}` - Revoke session
- `POST /api/user/settings/2fa/initialize` - Initialize 2FA
- `POST /api/user/settings/2fa/enable` - Enable 2FA
- `POST /api/user/settings/2fa/disable` - Disable 2FA
- `DELETE /api/user/settings/account` - Delete account

#### Transactions
- `GET /api/user/transactions` - Get transaction history
- `GET /api/user/transactions/{id}` - Get transaction details
- `POST /api/user/transactions/deposit` - Create deposit request
- `POST /api/user/transactions/withdrawal` - Create withdrawal request

#### Profit Growth
- `GET /api/user/profit-growths` - List user's profit growths
- `GET /api/user/profit-growths/{id}` - Get profit growth details
- `PUT /api/user/profit-growths/{id}` - Update profit growth (limited)
- `DELETE /api/user/profit-growths/{id}` - Delete profit growth
- `POST /api/user/profit-growths/transfer-funds` - Transfer funds (auto-creates if needed)
- `POST /api/user/profit-growths/{id}/transfer-funds` - Transfer to existing
- `POST /api/user/profit-growths/{id}/withdraw-funds` - Withdraw from profit growth

#### Simulated Transfers
- `GET /api/user/simulated-transfers` - List transfers
- `GET /api/user/simulated-transfers/sent` - Get sent transfers
- `GET /api/user/simulated-transfers/received` - Get received transfers
- `GET /api/user/simulated-transfers/{id}` - Get transfer details
- `POST /api/user/simulated-transfers` - Create transfer

#### Profile View Requests
- `POST /api/profile-view-requests/create` - Create request
- `GET /api/profile-view-requests/pending` - Get pending requests
- `POST /api/profile-view-requests/{id}/approve` - Approve request
- `POST /api/profile-view-requests/{id}/decline` - Decline request
- `GET /api/profile-view-requests/check-access/{id}` - Check access

### Admin Endpoints

#### User Management
- `GET /api/admin/users` - List all users
- `GET /api/admin/users/{id}` - Get user details
- `POST /api/admin/credits` - Credit funds to user
- `POST /api/admin/users/{id}/freeze` - Freeze user account
- `POST /api/admin/users/{id}/unfreeze` - Unfreeze user account
- `DELETE /api/admin/users/{id}` - Delete user

#### Profit Growth Management
- `POST /api/admin/profit-growths` - Create profit growth for user
- `PUT /api/admin/profit-growths/{id}` - Update any profit growth
- `POST /api/admin/profit-growths/calculate-growth` - Manually trigger growth calculation
- `DELETE /api/admin/profit-growths/{id}` - Delete profit growth

#### Transactions
- `GET /api/admin/transactions` - Get all transactions
- `GET /api/admin/reports` - Get reports

#### Deposits
- `GET /api/admin/deposits/pending` - Get pending deposits
- `POST /api/admin/deposits/{id}/confirm` - Confirm deposit
- `POST /api/admin/deposits/{id}/check` - Check deposit status

#### Withdrawals
- `GET /api/admin/withdrawals/pending` - Get pending withdrawals
- `POST /api/admin/withdrawals` - Create withdrawal
- `POST /api/admin/withdrawals/{id}/confirm` - Confirm withdrawal
- `POST /api/admin/withdrawals/{id}/cancel` - Cancel withdrawal

---

## Profit Growth System

### Overview
The Profit Growth system is the core feature that allows funds to automatically grow at a fixed rate through compound interest calculations.

### Growth Calculation

#### Default Settings
- **Growth Percentage**: 20% weekly
- **Growth Frequency**: Weekly
- **Daily Growth Rate**: 2.8% (20% ÷ 7 days = ~2.857%)

#### Calculation Logic

**During 7-Day Lock Period:**
- Growth is calculated **daily** at 2.8% on the full `current_balance`
- Formula: `new_balance = current_balance × (1 + 0.028)`
- Calculated every day until lock period ends

**After Lock Period:**
- Growth continues **daily** at 2.8% on the full balance
- All funds (including previous growth) continue to compound
- No lock restrictions after initial 7-day period

**For Other Percentages/Frequencies:**
- If growth percentage is not 20% or frequency is not weekly, uses standard frequency-based calculation
- During lock: Daily growth = (total_percentage ÷ 7) on locked_amount
- After lock: Uses frequency-based calculation (weekly, monthly, etc.)

### Lock Period Rules

1. **Initial Lock**: When funds are transferred to profit growth, they're locked for 7 days
2. **Lock Amount**: Only the transferred amount is tracked in `locked_amount`
3. **Daily Growth**: During lock, growth is calculated daily on full balance
4. **Auto-Unlock**: After 7 days, lock automatically releases
5. **Continued Growth**: Growth continues daily even after unlock

### Transfer Rules

1. **Weekly Limit**: Users can only transfer funds once per week
2. **Cooldown**: 7-day cooldown between transfers
3. **Lock Period**: Each transfer creates a new 7-day lock period
4. **Admin Deposits**: Admins can create profit growths with initial balance (no lock, but growth starts immediately)

### Scheduled Calculation

**Command**: `php artisan profit-growth:calculate`
**Schedule**: Runs every minute (via Laravel Scheduler)
**Logic**: 
- Only processes profit growths where `next_growth_calculation_at <= now()`
- Updates `next_growth_calculation_at` after each calculation
- Creates activity log entries for each calculation

### Manual Calculation

Admins can manually trigger growth calculation via:
- `POST /api/admin/profit-growths/calculate-growth`
- Can target specific profit growth or all due profit growths
- Can force calculation even if not due

---

## User Roles & Permissions

### Regular Users
- Create account and manage profile
- Deposit/withdraw real funds
- Transfer funds to profit growth accounts
- View own profit growth accounts
- Send/receive simulated transfers
- View other user profiles (with permission)
- Update own settings
- Cannot create profit growth accounts (admin-only)

### Administrators
- All regular user permissions
- Create profit growth accounts for any user
- Update/delete any profit growth account
- Credit/debit user funds
- Freeze/unfreeze user accounts
- Delete users
- View all transactions
- Confirm deposits/withdrawals
- Access admin dashboard
- Generate reports
- Manually trigger growth calculations

### Account States

**Active**: Normal account, full access
**Frozen**: Account restricted, cannot perform transactions
**Email Unverified**: Limited access until email verified

---

## Frontend Structure

### Page Routing
Custom JavaScript routing system (`js/routes.js`) maps route names to HTML files.

### Key Pages

#### Public Pages
- `home.html` - Landing page with calculator and FAQ
- `login.html` - User login
- `registration.html` - User registration
- `about-us.html` - About page
- `privacy-policy.html` - Privacy policy
- `terms-of-service.html` - Terms of service

#### User Pages
- `dashboard.html` - Main dashboard
- `my-profile-page.html` - User's own profile
- `edit-profile.html` - Edit profile
- `user-profile-view.html` - View other user's profile
- `profile-search.html` - Search users
- `simu-list-page.html` - Profit growth list
- `simu-detail-page.html` - Profit growth details
- `transaction-history.html` - Transaction history
- `deposit.html` - Deposit funds
- `withdrawal.html` - Withdraw funds
- `send-simu-funds.html` - Send simulated funds
- `received-transfer-funds.html` - Received transfers
- `sent-transfer.html` - Sent transfers
- `notification-page.html` - Notifications
- `activity-feed-page.html` - Activity feed
- `SETTINGS_PAGE.html` - Settings

#### Admin Pages
- `admin-dashboard.html` - Admin dashboard
- `admin-user-list.html` - User list
- `admin-user-detail.html` - User details
- `admin-credit-funds-page.html` - Credit funds
- `admin-deposits-page.html` - Manage deposits
- `admin-withdrawals-page.html` - Manage withdrawals
- `admin-freeze.html` - Freeze accounts
- `FROZEN_ACCOUNTS_LIST_PAGE.html` - Frozen accounts
- `ALL_TRANSACTIONS_ADMIN_PAGE.html` - All transactions
- `COMPLIANCE_EXPORT_ADMIN_PAGE.html` - Compliance export
- `REPORTS_ADMIN_PAGE.html` - Reports

### JavaScript Architecture

#### Core Files
- `js/config.js` - API configuration and constants
- `js/utils.js` - Utility functions (formatting, validation)
- `js/api.js` - API helper functions
- `js/forms.js` - Form validation and handling
- `js/modals.js` - Modal management
- `js/routes.js` - Routing system
- `js/main.js` - Main initialization

#### API Client
All API calls go through the `API` object:
```javascript
API.auth.login(email, password)
API.user.getProfile()
API.profitGrowth.list()
API.admin.creditFunds(userId, amount, reason)
```

### Styling

#### CSS Files
- `css/main.css` - Custom styles and utilities

#### Design System
- **Primary Green** (`#10B981`): Real funds, primary actions
- **Secondary Blue** (`#3B82F6`): Profit growth, secondary actions
- **Dark Slate** (`#0F172A`): Headers, dark backgrounds
- **Background** (`#F9FAFB`): Light background
- **Text Colors**: `#111827` (dark), `#6B7280` (gray)

#### Visual Indicators
- **Real Funds**: Green borders, solid lines, green accents
- **Profit Growth**: Blue borders, dashed lines, blue accents

---

## Deployment & Server Setup

### Server Requirements
- PHP 8.2+
- Composer
- Node.js (for asset compilation)
- Web server (Apache/Nginx)
- Database (SQLite/MySQL/PostgreSQL)

### Deployment Steps

1. **Upload Files**
   ```bash
   # Backend files to: /var/www/conchcapital/backend/
   # Frontend files to: /var/www/conchcapital/backend/public/
   ```

2. **Install Dependencies**
   ```bash
   cd /var/www/conchcapital/backend
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Edit .env with database, mail, etc.
   ```

4. **Database Setup**
   ```bash
   php artisan migrate --force
   php artisan db:seed (if needed)
   ```

5. **Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

6. **Laravel Scheduler**
   Add to crontab:
   ```bash
   * * * * * cd /var/www/conchcapital/backend && php artisan schedule:run >> /dev/null 2>&1
   ```

7. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   php artisan view:clear
   ```

### Upload Scripts
The project includes shell scripts for uploading files:
- `upload-all.sh` - Upload all files
- `upload_backend_files.sh` - Upload backend only
- `upload-dashboard.sh` - Upload dashboard
- Uses SSH key authentication (configured)

### Server Configuration

#### Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName conchcapital.co
    DocumentRoot /var/www/conchcapital/backend/public
    
    <Directory /var/www/conchcapital/backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name conchcapital.co;
    root /var/www/conchcapital/backend/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Security Features

### Authentication
- Laravel Sanctum session-based authentication
- CSRF protection
- Password hashing (bcrypt)
- Email verification required
- Two-factor authentication (2FA) support

### Authorization
- Role-based access control (admin vs user)
- Resource ownership checks
- Middleware protection on routes

### Data Protection
- SQL injection protection (Eloquent ORM)
- XSS protection (Laravel Blade escaping)
- CSRF tokens on forms
- Input validation and sanitization

### Account Security
- Account freezing capability
- Session management
- Password reset tokens
- Email change verification

### Financial Security
- Transaction validation
- Balance checks before operations
- Admin confirmation for deposits/withdrawals
- Activity logging for audit trail

---

## File Structure

```
project/
├── backend/                    # Laravel backend
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/
│   │   │       ├── CalculateProfitGrowth.php
│   │   │       └── CheckProfitGrowths.php
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │           ├── AdminController.php
│   │   │           ├── AuthController.php
│   │   │           ├── DashboardController.php
│   │   │           ├── ProfitGrowthController.php
│   │   │           └── ...
│   │   ├── Mail/              # Email templates
│   │   ├── Models/            # Eloquent models
│   │   └── Providers/
│   ├── config/                # Configuration files
│   ├── database/
│   │   ├── migrations/        # Database migrations
│   │   └── seeders/          # Database seeders
│   ├── routes/
│   │   ├── api.php           # API routes
│   │   ├── web.php           # Web routes
│   │   └── console.php       # Scheduled commands
│   ├── storage/              # Storage (logs, cache)
│   └── public/               # Public assets
│       └── index.php         # Entry point
├── css/
│   └── main.css              # Custom styles
├── js/
│   ├── config.js             # Configuration
│   ├── utils.js              # Utilities
│   ├── api.js                # API client
│   ├── forms.js              # Form handling
│   ├── modals.js             # Modal management
│   ├── routes.js             # Routing
│   └── main.js               # Main initialization
├── assets/                   # Static assets
│   ├── images/
│   └── fonts/
├── *.html                    # Frontend pages
└── upload-*.sh              # Deployment scripts
```

---

## Configuration

### Environment Variables (.env)

```env
APP_NAME=ConchCapital
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://conchcapital.co

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=conchcapital
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@conchcapital.co
MAIL_FROM_NAME="${APP_NAME}"

SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### JavaScript Configuration (js/config.js)

```javascript
const API_CONFIG = {
    baseURL: '/api',
    timeout: 30000,
    // ...
};
```

---

## Development Guide

### Local Setup

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd project
   ```

2. **Backend Setup**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

3. **Frontend Setup**
   ```bash
   # Serve static files (use any static server)
   # Or use Laravel's public directory
   ```

4. **Run Scheduler**
   ```bash
   # In separate terminal
   php artisan schedule:work
   ```

### Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter ProfitGrowthTest
```

### Code Style

```bash
# Format code (Laravel Pint)
./vendor/bin/pint
```

### Common Commands

```bash
# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Create migration
php artisan make:migration create_table_name

# Create model
php artisan make:model ModelName

# Create controller
php artisan make:controller Api/ControllerName

# Run scheduled command manually
php artisan profit-growth:calculate
```

### Debugging

- Enable `APP_DEBUG=true` in `.env` for development
- Check logs: `storage/logs/laravel.log`
- Use Laravel Tinker: `php artisan tinker`

---

## Additional Notes

### Profit Growth Calculation Details

The system uses compound interest formula:
```
Balance after N days = Initial × (1.028)^N
```

For 7 days: `1000 × (1.028)^7 ≈ 1200` (20% growth)

### Email Templates

All email templates are in `backend/resources/views/emails/`:
- `welcome.blade.php` - Welcome email
- `credit-notification.blade.php` - Credit notification
- `transfer-received.blade.php` - Transfer received
- Profile view request emails

### Activity Logging

All significant actions are logged:
- User actions (transfers, deposits, withdrawals)
- Admin actions (credits, freezes, etc.)
- System actions (growth calculations)

### Future Enhancements

Potential improvements:
- Real-time notifications (WebSockets)
- Advanced analytics dashboard
- Mobile app (React Native/Flutter)
- API rate limiting
- Multi-currency support
- Advanced reporting

---

## Support & Maintenance

### Regular Maintenance Tasks

1. **Daily**: Monitor growth calculations
2. **Weekly**: Review transaction logs
3. **Monthly**: Generate compliance reports
4. **Quarterly**: Security audit

### Monitoring

- Check Laravel logs: `storage/logs/`
- Monitor scheduled tasks
- Review error logs
- Check database performance

### Backup Strategy

- Database backups (daily)
- Code version control (Git)
- Configuration backups
- Email template backups

---

## License

[Specify license here]

## Contact

For questions or support, contact the development team.

---

**Last Updated**: January 2025
**Version**: 1.0.0
