# Gemini Prompt: Redesign Home Page for ConchCapital

## Project Context
I need you to redesign the home/landing page for **ConchCapital**, an investment education and simulation platform. The current home page is functional but could be more modern, visually engaging, and conversion-focused. I need a stunning, professional landing page that maintains all existing functionality while significantly improving the visual design and user experience.

## Current Home Page Structure

The home page (`home.html`) currently has:
- Header with navigation (Home, Features, About, Contact) and Sign In/Get Started buttons
- Hero section with headline, subheadline, and CTA buttons
- "How It Works" section with 3 feature cards
- Educational disclaimer section
- Footer with branding, links, and copyright
- Basic styling with Tailwind CSS

## Brand Identity & Design System

**Brand Name:** ConchCapital (Conch + Capital)

**Color Palette:**
- Primary Green: `#10B981` (for real funds, primary actions, brand accent)
- Secondary Blue: `#3B82F6` (for simulated funds, secondary actions)
- Dark Slate: `#0F172A` (for dark backgrounds, headers)
- Slate-800: `#1E293B` (for dark cards)
- Success Green: `#10B981`
- Background: `#F9FAFB` (light gray)
- Text Colors: `#111827` (dark), `#6B7280` (medium gray)

**Typography:**
- Primary Font: `Inter` (sans-serif) - weights: 400, 500, 600, 700
- Monospace: `JetBrains Mono` (for numbers/code)

**Design Style:**
- Modern, clean, professional financial platform aesthetic
- Trustworthy and educational feel
- Subtle gradients and shadows for depth
- Smooth transitions and hover effects
- Rounded corners (8px-16px typically)
- Premium, polished look

## Required Functionality (MUST PRESERVE)

### 1. Navigation & Routing (Keep these exact attributes):
- `data-route="home"` - for home link
- `data-route="login"` - for sign in button
- `data-route="register"` - for get started/register buttons
- `data-route="dashboard"` - for demo/watch demo buttons

### 2. Required Script Tags (MUST INCLUDE):
```html
<script src="js/config.js"></script>
<script src="js/utils.js"></script>
<script src="js/api.js"></script>
<script src="js/forms.js"></script>
<script src="js/modals.js"></script>
<script src="js/routes.js"></script>
<script src="js/main.js"></script>
```

### 3. Required CDN Links:
```html
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/main.css">
```

### 4. Body Attribute:
```html
<body class="..." data-page="home">
```

### 5. Brand Name (MUST USE):
- All instances must use: **ConchCapital** (not InvestEdu or any other name)
- Footer copyright: "© 2026 ConchCapital Platform. All rights reserved."

## Design Requirements

### Visual Enhancements Needed:

1. **Hero Section:**
   - More impactful, modern hero design
   - Better visual hierarchy
   - Consider adding subtle animations or gradients
   - More engaging CTA buttons with better styling
   - Maybe add a hero image, illustration, or animated graphic element

2. **Header/Navigation:**
   - More polished navigation bar
   - Better hover states and transitions
   - Consider sticky header with backdrop blur effect
   - Enhanced button styling

3. **Feature Cards:**
   - More visually appealing cards with better shadows/gradients
   - Icons with better styling
   - Hover effects and animations
   - Better spacing and typography

4. **Sections:**
   - Add more visual interest between sections
   - Consider adding:
     - Testimonials section
     - Stats/numbers section (e.g., "Join 10,000+ learners")
     - More detailed feature explanations
     - Trust badges or security indicators
   - Better section spacing and backgrounds

5. **Footer:**
   - More comprehensive footer design
   - Better organization of links
   - Social media icons (if applicable)
   - Enhanced branding presentation

6. **Overall Design:**
   - Use the beautiful design style from the login page as inspiration
   - Add subtle gradients, glassmorphism effects where appropriate
   - Smooth scroll animations
   - Better color usage and contrast
   - More engaging visual elements

### Design Inspiration:
- Modern fintech landing pages (Stripe, Linear, Vercel, Coinbase)
- SaaS landing pages with strong CTAs
- Educational platform landing pages
- Investment/financial platform aesthetics
- Use subtle animations and micro-interactions

## Content Structure (Preserve Core Message)

### Key Messages to Maintain:
1. **Real Wallet Balance** - Users can deposit real crypto, track actual funds
2. **Simulation Playground** - Test strategies with simulated funds (not real money)
3. **Educational Focus** - Platform is for learning, not investment advice
4. **Clear Distinction** - Real funds (withdrawable) vs Simulated funds (educational only)

### Sections to Include:
1. **Hero Section** - Compelling headline, subheadline, CTAs
2. **How It Works** - Feature cards explaining the platform
3. **Educational Disclaimer** - Clear messaging about educational purpose
4. **Real vs Simulated Comparison** - Visual distinction between fund types
5. **Footer** - Branding, links, copyright

### Optional Enhancements (You Can Add):
- Testimonials section
- Statistics/numbers section
- More detailed feature explanations
- Trust/security indicators
- FAQ section
- Pricing information (if applicable)

## Technical Constraints

1. **Must use Tailwind CSS** (via CDN)
2. **Must preserve all `data-route` attributes** for navigation
3. **Must work with existing JavaScript** files (no breaking changes)
4. **Must be fully responsive** (mobile, tablet, desktop)
5. **Must maintain accessibility** (proper semantic HTML, ARIA attributes)
6. **Must use brand colors** consistently
7. **Must include all required script tags**

## Current Page Elements to Reference

### Header:
- Logo: "ConchCapital"
- Navigation: Home, Features, About, Contact
- Buttons: "Sign In" and "Get Started"

### Hero Section:
- Headline: "Learn Investment Strategies Through Simulation"
- Subheadline: "Practice with simulated funds. Understand real strategies. No risk, all education."
- CTAs: "Get Started Free" and "Watch Demo"

### Features (How It Works):
1. Real Wallet Balance - Deposit real crypto, track actual funds
2. Simulation Playground - Test strategies with simulated funds
3. Learn & Share - Connect with other learners

### Educational Disclaimer:
- Platform is for educational purposes only
- Simulations are hypothetical
- Not financial advice
- Clear distinction between real and simulated funds

## Output Requirements

Generate a complete, production-ready `home.html` file that:
1. Is visually stunning and modern (matching the quality of the login page design)
2. Maintains all existing functionality and routing
3. Uses the ConchCapital brand colors and design system
4. Is fully responsive and accessible
5. Includes smooth animations and transitions
6. Has better visual hierarchy and engagement
7. Preserves all core messaging about real vs simulated funds
8. Works seamlessly with the existing project structure

## Design Quality Expectations

The new home page should:
- Look as polished and modern as the login page
- Feel premium and trustworthy (financial platform aesthetic)
- Have strong visual appeal that encourages sign-ups
- Use the brand colors strategically
- Include subtle animations and micro-interactions
- Have excellent typography and spacing
- Be conversion-focused with clear CTAs

## Example Structure (Reference Only - Feel Free to Enhance):

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConchCapital | Simulation-Based Learning</title>
    <!-- CDN links -->
</head>
<body data-page="home">
    <!-- Enhanced header with navigation -->
    <!-- Stunning hero section -->
    <!-- Beautiful feature sections -->
    <!-- Educational disclaimer -->
    <!-- Comprehensive footer -->
    <!-- All required scripts -->
</body>
</html>
```

## Notes

- The page should feel premium and professional
- Use subtle animations (fade-ins, slide-ins) for a polished feel
- Ensure the design matches the financial/investment platform aesthetic
- Make it visually distinct and more engaging than the current plain design
- Consider adding decorative elements (subtle patterns, gradients, or illustrations)
- Focus on conversion optimization with clear, prominent CTAs
- Maintain the educational/disclaimer messaging clearly

---

**Please generate a complete, beautiful, and functional home page that meets all the requirements above and matches the quality of the login page design.**
