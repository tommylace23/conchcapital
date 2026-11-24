/**
 * InvestEdu - Route Map
 * Centralized page routing for static HTML navigation
 */
const AppRoutes = {
    home: 'home.html',
    about: 'about-us.html',
    login: 'login.html',
    register: 'registration.html',
    forgotPassword: 'FORGOT_PASSWORD_PAGE.html',
    resetPassword: 'RESET_PASSWORD_PAGE.html',
    verifyEmail: 'EMAIL_VERIFICATION_PAGE.html',
    dashboard: 'dashboard.html',
    // Profit Growths (new)
    profitGrowths: 'simu-list-page.html',
    profitGrowthDetail: 'simu-detail-page.html',
    profitGrowthCreate: 'simu.html',
    // Simulations (legacy - kept for backward compatibility)
    simulations: 'simu-list-page.html',
    simulationDetail: 'simu-detail-page.html',
    simulationCreate: 'simu.html',
    transactions: 'transaction-history.html',
    deposits: 'deposit.html',
    withdrawals: 'withdrawal.html',
    sendSimFunds: 'send-simu-funds.html',
    receivedSimFunds: 'received-transfer-funds.html',
    sentSimFunds: 'sent-transfer.html',
    profile: 'my-profile-page.html',
    userProfileView: 'user-profile-view.html',
    editProfile: 'edit-profile.html',
    profileSearch: 'profile-search.html',
    notifications: 'notification-page.html',
    activityFeed: 'activity-feed-page.html',
    settings: 'SETTINGS_PAGE.html',
    adminDashboard: 'admin-dashboard.html',
    adminUsers: 'admin-user-list.html',
    adminUserDetail: 'admin-user-detail.html',
    adminTransactions: 'ALL_TRANSACTIONS_ADMIN_PAGE.html',
    adminCredits: 'admin-credit-funds-page.html',
    adminDeposits: 'admin-deposits-page.html',
    adminWithdrawals: 'admin-withdrawals-page.html',
    adminWithdrawFunds: 'admin-withdraw-funds-page.html',
    adminFreezes: 'admin-freeze.html',
    adminFrozenList: 'FROZEN_ACCOUNTS_LIST_PAGE.html',
    adminUnfreeze: 'UNFREEZE_ACCOUNT_PAGE.html',
    adminCompliance: 'COMPLIANCE_EXPORT_ADMIN_PAGE.html',
    adminReports: 'REPORTS_ADMIN_PAGE.html',
    adminSettings: 'PLATFORM_SETTINGS_ADMIN_PAGE.html',
    adminActionsLog: 'ADMIN_ACTIONS_LOG_PAGE.html',
    adminSimTransfers: 'SIMULATED_TRANSFERS_ADMIN_PAGE.html',
    adminLogin: 'admin-login.html',
    termsOfService: 'terms-of-service.html',
    privacyPolicy: 'privacy-policy.html'
};

function getRoute(routeName) {
    return AppRoutes[routeName] || '#';
}

function wireRouteLinks() {
    document.querySelectorAll('[data-route]').forEach(element => {
        const routeName = element.getAttribute('data-route');
        if (!routeName) {
            return;
        }

        const href = getRoute(routeName);
        const tagName = element.tagName.toLowerCase();

        if (tagName === 'a') {
            element.setAttribute('href', href);
            // Add click handler to ensure navigation works even if href was originally "#"
            element.addEventListener('click', function(e) {
                if (href && href !== '#') {
                    e.preventDefault();
                    window.location.href = href;
                }
            });
            return;
        }

        element.addEventListener('click', () => {
            if (href !== '#') {
                window.location.href = href;
            }
        });
    });
}

// Expose helpers for page scripts
window.AppRoutes = AppRoutes;
window.RouteUtils = {
    getRoute,
    wireRouteLinks
};
