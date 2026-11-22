# InvestEdu - Project Structure

This document explains the project structure and how to use the CSS and JavaScript files.

## 📁 Project Structure

```
test/
├── css/
│   └── main.css              # Main stylesheet with all custom styles
├── js/
│   ├── config.js            # Application configuration
│   ├── utils.js             # Utility functions
│   ├── api.js               # API helper functions
│   ├── forms.js             # Form validation and handling
│   ├── modals.js            # Modal management
│   └── main.js              # Main initialization file
├── assets/
│   ├── images/              # Image assets
│   └── fonts/               # Custom fonts (if any)
└── *.html                   # HTML pages
```

## 🎨 CSS Usage

### Include in HTML:

```html
<head>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Main CSS -->
    <link rel="stylesheet" href="css/main.css">
</head>
```

### What's in main.css:

- **CSS Variables**: Brand colors, fonts, spacing, shadows
- **Base Styles**: Typography, body styles
- **Utility Classes**: `.text-real`, `.bg-sim`, `.mono`, etc.
- **Components**: Buttons, cards, forms, badges, alerts
- **Responsive Utilities**: Mobile-friendly sidebar
- **Animations**: Fade-in, spin animations

## 📜 JavaScript Usage

### Include in HTML (before closing `</body>` tag):

```html
<!-- Configuration (load first) -->
<script src="js/config.js"></script>

<!-- Utilities -->
<script src="js/utils.js"></script>

<!-- API Helpers -->
<script src="js/api.js"></script>

<!-- Form Handling -->
<script src="js/forms.js"></script>

<!-- Modal Management -->
<script src="js/modals.js"></script>

<!-- Main Initialization -->
<script src="js/main.js"></script>

<!-- Page-specific scripts (if any) -->
<script>
    // Your page-specific JavaScript here
</script>
```

## 🔧 Available JavaScript Functions

### Utils (from `utils.js`)

```javascript
// Format currency
Utils.formatCurrency(1000.50); // "$1,000.50"

// Format date
Utils.formatDate(new Date()); // "01/15/2025"

// Show toast notification
Utils.showToast('Success!', 'success', 5000);

// Toggle password visibility
Utils.togglePasswordVisibility('password-input-id');

// Validate email
Utils.isValidEmail('user@example.com'); // true

// Validate password
Utils.validatePassword('MyPass123'); // { isValid: true, errors: [] }
```

### Forms (from `forms.js`)

```javascript
// Validate form
const form = document.getElementById('my-form');
const rules = {
    email: { required: true, email: true },
    password: { required: true, minLength: 8 }
};
Forms.validateForm(form, rules);

// Get form data
const data = Forms.getFormData(form);

// Set form data
Forms.setFormData(form, { email: 'user@example.com' });

// Setup character counter
const textarea = document.getElementById('bio');
const counter = document.getElementById('char-counter');
Forms.setupCharacterCounter(textarea, counter, 500);
```

### Modals (from `modals.js`)

```javascript
// Show modal
Modals.show({
    title: 'My Modal',
    content: '<p>Modal content here</p>',
    size: 'md', // sm, md, lg, xl
    buttons: [
        {
            text: 'Cancel',
            className: 'btn-outline',
            onClick: () => console.log('Cancelled')
        },
        {
            text: 'Save',
            className: 'btn-primary',
            onClick: () => console.log('Saved')
        }
    ]
});

// Show confirmation
Modals.confirm({
    title: 'Delete Item',
    message: 'Are you sure you want to delete this item?',
    confirmText: 'Delete',
    cancelText: 'Cancel'
}).then(confirmed => {
    if (confirmed) {
        // User confirmed
    }
});

// Show alert
Modals.alert({
    title: 'Success',
    message: 'Operation completed successfully!',
    type: 'success'
});
```

### API (from `api.js`)

```javascript
// Authentication
await API.auth.login('user@example.com', 'password');
await API.auth.register({ email, password, username });

// User operations
await API.user.getProfile();
await API.user.updateProfile({ display_name: 'John Doe' });
await API.user.getTransactions({ page: 1, limit: 20 });

// Admin operations
await API.admin.getUsers({ search: 'john' });
await API.admin.creditFunds(userId, 100, 'Promotional credit');
```

## 🎯 Example: Updated HTML File

Here's how to update your HTML files to use the new structure:

### Before:
```html
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .text-real { color: #10B981; }
    </style>
</head>
```

### After:
```html
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title | InvestEdu</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Your HTML content -->
    
    <!-- JavaScript Files (before closing </body>) -->
    <script src="js/config.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/api.js"></script>
    <script src="js/forms.js"></script>
    <script src="js/modals.js"></script>
    <script src="js/main.js"></script>
    
    <!-- Page-specific scripts -->
    <script>
        // Your page-specific JavaScript
        document.getElementById('my-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Forms.getFormData(e.target);
            try {
                await API.user.updateProfile(data);
                Utils.showToast('Profile updated!', 'success');
            } catch (error) {
                Utils.showToast('Error updating profile', 'error');
            }
        });
    </script>
</body>
```

## 🚀 Next Steps

1. **Update HTML Files**: Replace inline styles and scripts with references to the new CSS/JS files
2. **Test Functionality**: Make sure all pages work correctly with the new structure
3. **Customize**: Modify `config.js` with your API endpoints and settings
4. **Build Process** (Optional): Set up a build process (Webpack, Vite, etc.) for production

## 📝 Notes

- All JavaScript files use global variables (no module system) for simplicity
- You can convert to ES6 modules later if needed
- The CSS uses CSS variables for easy theming
- All utility functions are available globally via `Utils`, `Forms`, `Modals`, `API`

## 🔗 Dependencies

- **Tailwind CSS**: Via CDN (can be replaced with local build)
- **Font Awesome**: Via CDN for icons
- **Google Fonts**: Inter and JetBrains Mono fonts

---

For questions or issues, refer to the individual file comments or the project specification.
