/**
 * InvestEdu - Configuration File
 * Centralized configuration for the application
 */

const envApiBaseUrl = (typeof process !== 'undefined' && process.env && process.env.API_BASE_URL)
    ? process.env.API_BASE_URL
    : null;
const browserApiBaseUrl = (typeof window !== 'undefined' && window.API_BASE_URL)
    ? window.API_BASE_URL
    : null;

const AppConfig = {
    // API Configuration
    api: {
        baseUrl: envApiBaseUrl || browserApiBaseUrl || '',
        timeout: 30000, // 30 seconds
        endpoints: {
            auth: {
                login: '/api/auth/login',
                register: '/api/auth/register',
                logout: '/api/auth/logout',
                forgotPassword: '/api/auth/forgot-password',
                resetPassword: '/api/auth/reset-password',
                verifyEmail: '/api/auth/verify-email'
            },
            user: {
                profile: '/api/user/profile',
                updateProfile: '/api/user/profile',
                settings: '/api/user/settings',
                transactions: '/api/user/transactions',
                simulations: '/api/user/simulations'
            },
            admin: {
                users: '/api/admin/users',
                transactions: '/api/admin/transactions',
                credits: '/api/admin/credits',
                freezes: '/api/admin/freezes',
                reports: '/api/admin/reports'
            }
        }
    },

    // Application Settings
    app: {
        name: 'InvestEdu',
        version: '1.0.0',
        currency: 'USD',
        currencySymbol: '$',
        dateFormat: 'MM/DD/YYYY',
        timeFormat: 'HH:mm'
    },

    // Validation Rules
    validation: {
        password: {
            minLength: 8,
            requireUppercase: true,
            requireLowercase: true,
            requireNumber: true,
            requireSpecialChar: false
        },
        username: {
            minLength: 3,
            maxLength: 20,
            pattern: /^[a-zA-Z0-9_]+$/
        },
        email: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        }
    },

    // UI Settings
    ui: {
        itemsPerPage: 20,
        debounceDelay: 300, // milliseconds
        toastDuration: 5000, // milliseconds
        modalAnimationDuration: 300 // milliseconds
    },

    // Feature Flags
    features: {
        twoFactorAuth: true,
        profileSearch: true,
        simulatedTransfers: true,
        emailVerification: true
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AppConfig;
}
