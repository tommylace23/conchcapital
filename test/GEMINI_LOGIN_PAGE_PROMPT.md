# Gemini Prompt: Redesign Login Page for InvestEdu

## Project Context
I need you to redesign the login page for **InvestEdu**, an investment education platform. The current login page is too plain and lacks visual appeal. I need a modern, professional, and visually engaging login page that maintains all existing functionality.

## Current Login Page Structure

The login page (`login.html`) currently has:
- Basic white card on light gray background
- Simple form with email/username and password fields
- Password visibility toggle
- Remember me checkbox
- Forgot password link
- Sign up link at bottom
- Error alert box (styled red alert with close button)
- Form submission handler with API integration

## Brand Identity & Design System

**Brand Name:** InvestEdu (Invest + Edu)

**Color Palette:**
- Primary Green: `#10B981` (for real funds, primary actions)
- Secondary Blue: `#3B82F6` (for simulated funds, secondary actions)
- Dark Slate: `#0F172A` (for dark backgrounds, headers)
- Slate-800: `#1E293B` (for dark cards)
- Success Green: `#10B981`
- Error Red: `#EF4444` or `#DC2626`
- Background: `#F9FAFB` (light gray)

**Typography:**
- Primary Font: `Inter` (sans-serif) - weights: 400, 500, 600, 700
- Monospace: `JetBrains Mono` (for numbers/code)

**Design Style:**
- Modern, clean, professional
- Financial/investment platform aesthetic
- Subtle gradients and shadows for depth
- Smooth transitions and hover effects
- Rounded corners (8px-12px typically)

## Required Functionality (MUST PRESERVE)

### 1. Form Elements (Keep these exact IDs and structure):
- `id="loginForm"` - form element
- `id="identifier"` - email/username input
- `id="password"` - password input
- `id="remember-me"` - checkbox
- `id="loginBtn"` - submit button
- `id="loginBtnText"` - button text span
- `id="loginErrorAlert"` - error alert container (hidden by default)
- `id="loginErrorMessage"` - error message text
- `data-toggle-password="password"` - password visibility toggle button
- `data-route="forgotPassword"` - forgot password link
- `data-route="register"` - sign up link

### 2. JavaScript Functions (MUST INCLUDE):
```javascript
// Function to show login error alert
function showLoginError(message) {
    const errorAlert = document.getElementById('loginErrorAlert');
    const errorMessage = document.getElementById('loginErrorMessage');
    
    if (errorAlert && errorMessage) {
        if (message === 'Invalid credentials.') {
            errorMessage.textContent = 'The email/username or password you entered is incorrect. Please check your credentials and try again.';
        } else {
            errorMessage.textContent = message;
        }
        errorAlert.classList.remove('hidden');
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Function to close login error alert
function closeLoginError() {
    const errorAlert = document.getElementById('loginErrorAlert');
    if (errorAlert) {
        errorAlert.classList.add('hidden');
    }
    const identifierInput = document.getElementById('identifier');
    const passwordInput = document.getElementById('password');
    if (identifierInput) {
        identifierInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    }
    if (passwordInput) {
        passwordInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    }
}
```

### 3. Form Submission Handler (MUST PRESERVE):
```javascript
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const identifierInput = document.getElementById('identifier');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember-me');
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    
    const identifier = identifierInput.value.trim();
    const password = passwordInput.value;
    const remember = rememberCheckbox.checked;

    if (!identifier || !password) {
        Utils?.showToast?.('Please enter both email/username and password', 'error');
        return;
    }

    loginBtn.disabled = true;
    loginBtnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Signing in...';

    try {
        await API.auth.csrf();
        const response = await API.auth.login(identifier, password, remember);
        
        Utils?.showToast?.('Login successful!', 'success');
        
        setTimeout(() => {
            if (response.user && response.user.is_admin) {
                window.location.href = '/admin-dashboard.html';
            } else {
                window.location.href = '/dashboard.html';
            }
        }, 500);

    } catch (error) {
        console.error('Login error:', error);
        const message = error.response?.message || error.message || 'Invalid credentials.';
        
        showLoginError(message);
        
        identifierInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        passwordInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        
        identifierInput.addEventListener('input', function() {
            this.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        }, { once: true });
        
        passwordInput.addEventListener('input', function() {
            this.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        }, { once: true });
        
    } finally {
        loginBtn.disabled = false;
        loginBtnText.textContent = 'Sign In';
    }
});
```

### 4. Required Script Tags (MUST INCLUDE):
```html
<script src="js/config.js"></script>
<script src="js/utils.js"></script>
<script src="js/api.js"></script>
<script src="js/forms.js"></script>
<script src="js/modals.js"></script>
<script src="js/routes.js"></script>
<script src="js/main.js"></script>
```

### 5. Required CDN Links:
```html
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/main.css">
```

### 6. Body Attribute:
```html
<body class="..." data-page="login">
```

## Design Requirements

### Visual Enhancements Needed:
1. **Background:** Add a subtle gradient, pattern, or image background instead of plain gray
2. **Card Design:** Enhance the white card with:
   - Better shadows (multiple layers for depth)
   - Subtle border or gradient border
   - Maybe a slight glassmorphism effect
3. **Branding:** Add InvestEdu logo/branding prominently at the top
4. **Input Fields:** 
   - Add icons inside input fields (envelope for email, lock for password)
   - Better focus states with animations
   - Subtle background colors or borders
5. **Button:** 
   - More prominent with gradient or better styling
   - Hover effects and animations
6. **Layout:** 
   - Consider split-screen design (left side with branding/graphics, right side with form)
   - OR centered design with enhanced visual elements
7. **Typography:** 
   - Better hierarchy and spacing
   - More engaging welcome message
8. **Color Accents:** 
   - Use brand colors strategically (green for primary actions, blue for links)
   - Subtle color gradients

### Design Inspiration:
- Modern fintech/login pages (Stripe, Linear, Vercel)
- Professional investment platforms
- Clean, modern SaaS login pages
- Use subtle animations and transitions

## Technical Constraints

1. **Must use Tailwind CSS** (via CDN)
2. **Must preserve all IDs** mentioned above
3. **Must work with existing JavaScript** files (no breaking changes)
4. **Must be responsive** (mobile-friendly)
5. **Must maintain accessibility** (proper labels, ARIA attributes)
6. **Error alert must remain functional** with the exact structure
7. **Password toggle must work** (uses `data-toggle-password` attribute handled by `js/main.js`)

## Output Requirements

Generate a complete, production-ready `login.html` file that:
1. Is visually stunning and modern
2. Maintains all existing functionality
3. Uses the brand colors and design system
4. Is fully responsive
5. Includes all required JavaScript code
6. Has proper error handling UI
7. Works seamlessly with the existing project structure

## Example Structure (Reference Only - Feel Free to Enhance):

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | InvestEdu</title>
    <!-- CDN links -->
</head>
<body>
    <!-- Enhanced login page with modern design -->
    <!-- Must include all required elements and IDs -->
    <!-- Must include all JavaScript functions -->
</body>
</html>
```

## Notes

- The page should feel premium and professional
- Use subtle animations (fade-ins, slide-ins) for a polished feel
- Ensure the design matches the financial/investment platform aesthetic
- Make it visually distinct from the current plain design
- Consider adding decorative elements (subtle patterns, gradients, or illustrations)

---

**Please generate a complete, beautiful, and functional login page that meets all the requirements above.**
