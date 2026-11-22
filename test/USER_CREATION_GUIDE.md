# User Creation Guide

## How to Add Your Own Users

### Step 1: Edit `user_names.json`

Open the `user_names.json` file and add your users. Each user needs at minimum:
- `first_name` (required)
- `last_name` (required)

Optional fields:
- `username` - If not provided, will be auto-generated
- `email` - If not provided, will be auto-generated from username
- `bio` - User's bio/description
- `real_balance` - Real wallet balance (default: random 0-500000)
- `simulated_balance` - Simulated balance (default: random 50000-500000)
- `password` - Password (default: "password")
- `created_at` - Account creation date (default: random date in last 6 months)
- `last_active_at` - Last active date (default: random recent date)

### Example:

```json
[
  {
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe123",
    "bio": "Building wealth through strategic investments."
  },
  {
    "first_name": "Jane",
    "last_name": "Smith",
    "username": "janesmith456",
    "bio": "Crypto enthusiast and trader."
  }
]
```

### Step 2: Run the Script

**On your local machine (for testing):**
```bash
php create_custom_users.php
```

**On the server:**
```bash
cd /var/www/conchcapital
php create_custom_users.php
```

### Notes:

- The script will skip users if username or email already exists
- All users will have `email_verified_at` set (verified accounts)
- Users are created with `is_admin = false` and `is_frozen = false`
- If you don't specify a username, one will be auto-generated
- If you don't specify an email, it will be generated from the username

### Tips:

- You can add as many users as you want (50, 100, etc.)
- Each user will appear on the profile search page
- Users are ordered by most recently active, then by creation date
- The profile search page shows 30 users per page with pagination
