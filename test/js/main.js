/**
 * InvestEdu - Main JavaScript File
 * Initializes the application and sets up global event listeners
 */

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('InvestEdu Application Initialized');
    
    // Initialize common features
    initRouteLinks();
    initPasswordToggles();
    initFormValidations();
    initTooltips();
    initSidebar();
    
    // Global safety net: never let a rejected promise surface raw JSON
    window.addEventListener('unhandledrejection', (event) => {
        const err = event.reason;
        if (!err) return;
        const msg = (err && err.message) ? err.message : 'Something went wrong. Please try again.';
        Utils?.showToast?.(msg, 'error');
    });

    // Initialize page-specific features
    const page = document.body.getAttribute('data-page');
    if (page === 'dashboard') {
        initDashboard();
    }

    // Guard every admin page (the login page is exempt)
    if (page && page.indexOf('admin') === 0 && page !== 'adminLogin') {
        guardAdminPage();
    }

    // Account completion nudges (email verification, 2FA)
    if (page === 'dashboard' || page === 'profile') {
        initAccountNudges();
    }

    // Show the impersonation banner wherever it applies
    if (page !== 'adminLogin') {
        initImpersonationBanner();
    }
});

/**
 * Render a fixed banner while an admin is viewing as a client.
 */
async function initImpersonationBanner() {
    if (typeof API === 'undefined' || !API.impersonationStatus) {
        return;
    }

    let status;
    try {
        status = await API.impersonationStatus();
    } catch (error) {
        return;
    }

    if (!status || !status.impersonating) {
        return;
    }

    const who = (status.viewing_as && (status.viewing_as.display_name || status.viewing_as.username)) || 'client';

    const bar = document.createElement('div');
    bar.style.cssText = [
        'position:fixed', 'left:0', 'right:0', 'bottom:0', 'z-index:9999',
        'background:#0F172A', 'color:#fff', 'padding:10px 16px',
        'display:flex', 'align-items:center', 'justify-content:center', 'gap:14px',
        'font-size:13px', 'font-weight:600', 'box-shadow:0 -4px 16px rgba(0,0,0,.2)'
    ].join(';');
    bar.innerHTML = '<span>Viewing as <strong style="color:#10B981">' + Utils.escapeHtml(who) + '</strong> — the client is not notified.</span>';

    const back = document.createElement('button');
    back.textContent = 'Return to admin';
    back.style.cssText = 'background:#10B981;color:#fff;border:0;border-radius:8px;padding:6px 14px;font-weight:800;cursor:pointer';
    back.addEventListener('click', async () => {
        back.disabled = true;
        try {
            await API.stopImpersonation();
            window.location.href = 'admin-dashboard.html';
        } catch (error) {
            back.disabled = false;
        }
    });

    bar.appendChild(back);
    document.body.appendChild(bar);
}

/**
 * Redirect non-admins away from admin pages.
 * Server-side checks remain authoritative; this only avoids broken screens.
 */
async function guardAdminPage() {
    if (typeof API === 'undefined' || !API.user || !API.user.getProfile) {
        return;
    }

    try {
        // While impersonating we are acting as a client — don't bounce.
        try {
            const st = await API.impersonationStatus();
            if (st && st.impersonating) {
                return;
            }
        } catch (ignored) { /* fall through */ }

        const res = await API.user.getProfile();
        const user = (res && res.user) || res || {};

        if (!user.is_admin) {
            window.location.href = 'admin-login.html?denied=1';
        }
    } catch (error) {
        // 401 is already handled by the API layer (redirects to login)
        const status = error && error.status;
        if (status === 403) {
            window.location.href = 'admin-login.html?denied=1';
        }
    }
}

/**
 * Initialize password visibility toggles
 */
function initPasswordToggles() {
    if (typeof Utils === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle-password');
            Utils.togglePasswordVisibility(targetId);
        });
    });
}

/**
 * Initialize form validations
 */
function initFormValidations() {
    if (typeof Forms === 'undefined') {
        return;
    }

    // Add real-time validation to forms
    document.querySelectorAll('form[data-validate]').forEach(form => {
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                // Validate on blur
                const rules = JSON.parse(form.getAttribute('data-rules') || '{}');
                const fieldRules = rules[this.name] || {};
                const validation = Forms.validateField(this, fieldRules);
                
                if (!validation.isValid) {
                    Forms.showFieldError(this, validation.errors[0]);
                } else {
                    Forms.clearFieldError(this);
                }
            });
        });
    });
}

/**
 * Initialize route-based navigation links
 */
function initRouteLinks() {
    if (typeof RouteUtils === 'undefined') {
        return;
    }

    RouteUtils.wireRouteLinks();
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    // Simple tooltip implementation
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip absolute bg-slate-900 text-white text-xs px-2 py-1 rounded z-50';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.top = `${this.offsetTop - 30}px`;
            tooltip.style.left = `${this.offsetLeft + this.offsetWidth / 2}px`;
            tooltip.style.transform = 'translateX(-50%)';
            document.body.appendChild(tooltip);
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

/**
 * Initialize sidebar (mobile menu)
 */
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('hidden');
            }
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            this.classList.add('hidden');
        });
    }
}

/**
 * Global error handler
 */
window.addEventListener('error', function(event) {
    console.error('Global Error:', event.error);
    // You can add error reporting here
});

/**
 * Initialize dashboard page
 */
async function initDashboard() {
    try {
        // Load dashboard data
        const data = await API.user.getDashboard();
        
        // Update user info in sidebar
        updateUserInfo(data.user);
        
        // Update balances
        updateBalances(data.user, data.statistics);
        
        // Update recent activity
        updateRecentActivity(data.recent_transactions, data.recent_activity);
        
        // Update community transfers
        updateCommunityTransfers(data);
        
        // Update statistics
        updateStatistics(data.statistics);
        
    } catch (error) {
        console.error('Failed to load dashboard:', error);
        
        // If unauthenticated, redirect to login (handled in API.js, but check here too)
        if (error.status === 401 || error.status === 419) {
            if (window.location.pathname !== '/login.html' && !window.location.pathname.includes('login')) {
                window.location.href = '/login.html';
                return;
            }
        }
        
        // Show error message to user
        if (error.response?.message) {
            Utils?.showToast?.(error.response.message, 'error');
        } else if (error.message && error.message.includes('unauthenticated')) {
            Utils?.showToast?.('Please log in to continue', 'error');
            setTimeout(() => {
                window.location.href = '/login.html';
            }, 2000);
        }
    }
}

/**
 * Get initials from name
 */
function getInitials(name) {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

/**
 * Update transfer section visibility based on profit growths
 */
async function updateTransferSection(statistics) {
    const transferSection = document.getElementById('transferSection');
    if (!transferSection) return;
    
    try {
        // Load user's profit growths
        const response = await API.user.getProfitGrowths({ per_page: 100 });
        const profitGrowths = response.data || response || [];
        
        // Filter for active/paused profit growths
        const activeProfitGrowths = profitGrowths.filter(pg => 
            pg.status === 'active' || pg.status === 'paused'
        );
        
        // Show transfer section only if user has at least one active profit growth
        if (activeProfitGrowths.length > 0) {
            transferSection.classList.remove('hidden');
        } else {
            transferSection.classList.add('hidden');
        }
    } catch (error) {
        console.error('Failed to load profit growths for transfer section:', error);
        // Hide section on error to avoid confusion
        transferSection.classList.add('hidden');
    }
}

/**
 * Update user info in sidebar
 */
function updateUserInfo(user) {
    if (!user) return;
    
    // Update avatar
    const avatarContainer = document.getElementById('sidebarAvatarContainer');
    const avatarImage = document.getElementById('sidebarAvatarImage');
    const avatarInitials = document.getElementById('sidebarAvatarInitials');
    
    if (avatarContainer && avatarImage && avatarInitials) {
        // Get initials for fallback
        const displayName = user.display_name || user.name || user.username || 'User';
        const initials = getInitials(displayName);
        avatarInitials.textContent = initials;
        
        // If user has an avatar_url, use it
        if (user.avatar_url) {
            // Ensure the URL is absolute if it's a relative path
            let imageUrl = user.avatar_url;
            if (imageUrl.startsWith('/storage/')) {
                // If it's a relative path, use it as-is (browser will resolve it)
                imageUrl = imageUrl;
            } else if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                // If no protocol, assume it's relative to root
                imageUrl = '/' + imageUrl.replace(/^\//, '');
            }
            
            avatarImage.src = imageUrl;
            avatarImage.onload = function() {
                // Image loaded successfully
                avatarImage.classList.remove('hidden');
                avatarInitials.classList.add('hidden');
            };
            avatarImage.onerror = function() {
                // If image fails to load, fall back to initials
                console.error('Failed to load avatar image:', imageUrl);
                avatarImage.classList.add('hidden');
                avatarInitials.classList.remove('hidden');
            };
        } else {
            // Fall back to initials
            avatarImage.classList.add('hidden');
            avatarInitials.classList.remove('hidden');
        }
    }
    
    // Update name
    const nameElement = document.getElementById('sidebarUserName');
    if (nameElement) {
        nameElement.textContent = user.display_name || user.name || user.username || 'User';
    }
    
    // Update member status (could be based on balance or other criteria)
    const memberElement = document.getElementById('sidebarUserStatus');
    if (memberElement) {
        const balance = parseFloat(user.real_balance || 0);
        memberElement.textContent = balance > 0 ? 'Active Member' : 'Member';
    }
}

/**
 * Update balance displays
 */
async function updateBalances(user, statistics) {
    if (!user) return;
    
    // Real balance - Primary display (USD)
    const realBalanceElement = document.getElementById('realWalletBalance');
    if (realBalanceElement) {
        const balance = parseFloat(user.real_balance || 0);
        realBalanceElement.textContent = `$${balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        
        // Calculate and display BTC equivalent
        const btcElement = document.getElementById('realWalletBalanceBTC');
        if (btcElement && balance > 0) {
            try {
                // Fetch current BTC price from CoinGecko API (free, no API key needed)
                const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd');
                const data = await response.json();
                const btcPrice = data.bitcoin?.usd || 42000; // Fallback to ~$42k if API fails
                
                // Calculate BTC equivalent
                const btcAmount = balance / btcPrice;
                btcElement.textContent = `≈ ${btcAmount.toFixed(8)} BTC`;
            } catch (error) {
                console.error('Failed to fetch BTC price:', error);
                // Fallback: use approximate price of $42,000
                const btcAmount = balance / 42000;
                btcElement.textContent = `≈ ${btcAmount.toFixed(8)} BTC`;
            }
        } else if (btcElement) {
            btcElement.textContent = '≈ 0.00000000 BTC';
        }
    } else {
        // Fallback: try to find by class selector (for backward compatibility)
        const fallbackElement = document.querySelector('.border-real h2');
        if (fallbackElement) {
            const balance = parseFloat(user.real_balance || 0);
            fallbackElement.textContent = `$${balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    }
    
    // Profit Growth Balance (sum of all profit growths' current_balance)
    const profitGrowthBalanceElement = document.getElementById('totalProfitGrowthBalance');
    if (profitGrowthBalanceElement) {
        // Use total_profit_growth_balance from statistics if available, otherwise calculate
        let totalBalance = 0;
        if (statistics && statistics.total_profit_growth_balance !== undefined) {
            totalBalance = parseFloat(statistics.total_profit_growth_balance || 0);
        } else {
            // Fallback: use simulated_balance (legacy)
            totalBalance = parseFloat(user.simulated_balance || 0);
        }
        profitGrowthBalanceElement.textContent = `$${totalBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    
    // Legacy: Simulated balance (for backward compatibility - try to find by class if ID not found)
    const simBalanceElement = document.querySelector('.border-sim h2:not(#totalProfitGrowthBalance)');
    if (simBalanceElement && !profitGrowthBalanceElement) {
        const balance = parseFloat(user.simulated_balance || 0);
        simBalanceElement.textContent = `$${balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
}

/**
 * Update recent activity table
 */
function updateRecentActivity(transactions, activityLogs) {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;
    
    // Clear existing rows (except the first 3 which are examples)
    const existingRows = tbody.querySelectorAll('tr');
    existingRows.forEach(row => row.remove());
    
    // Combine and sort by date (most recent first)
    const allActivities = [];
    
    // Add transactions (exclude pending deposits - they shouldn't show until admin confirms)
    if (transactions && transactions.length > 0) {
        transactions
            .filter(tx => {
                // Don't show pending deposits in recent activity
                // They will only appear after admin confirms them
                if (tx.type === 'deposit' && tx.status === 'pending') {
                    return false;
                }
                return true;
            })
            .slice(0, 5)
            .forEach(tx => {
                allActivities.push({
                    type: 'transaction',
                    action: tx.type,
                    fund_type: tx.fund_type,
                    amount: tx.amount,
                    currency: tx.currency || 'USD',
                    description: tx.description || `${tx.type} transaction`,
                    status: tx.status,
                    date: tx.created_at
                });
            });
    }
    
    // Add activity logs
    if (activityLogs && activityLogs.length > 0) {
        activityLogs.slice(0, 5).forEach(log => {
            allActivities.push({
                type: 'activity',
                action: log.action,
                fund_type: log.fund_type,
                amount: log.amount,
                currency: log.currency || 'USD',
                description: log.description || log.action,
                status: 'completed',
                date: log.created_at
            });
        });
    }
    
    // Sort by date (most recent first)
    allActivities.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    // Display top 5
    allActivities.slice(0, 5).forEach(activity => {
        const row = createActivityRow(activity);
        tbody.appendChild(row);
    });
    
    // If no activities, show empty state
    if (allActivities.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="4" class="px-8 py-8 text-center text-slate-400">
                <p class="text-sm">No recent activity</p>
            </td>
        `;
        tbody.appendChild(row);
    }
}

/**
 * Create an activity table row
 */
function createActivityRow(activity) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50/50 transition';
    
    const fundTypeClass = activity.fund_type === 'real' ? 'real' : 'sim';
    const fundTypeBg = activity.fund_type === 'real' ? 'bg-green-100' : 'bg-blue-100';
    const fundTypeText = activity.fund_type === 'real' ? 'text-real' : 'text-sim';
    
    const amount = parseFloat(activity.amount || 0);
    const amountSign = activity.action === 'deposit' || activity.action === 'transfer_received' || activity.action === 'admin_credit' ? '+' : '';
    const amountDisplay = `${amountSign}$${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    
    const statusClass = activity.status === 'completed' ? 'bg-green-100 text-real' : 
                       activity.status === 'pending' ? 'bg-yellow-100 text-yellow-600' : 
                       'bg-red-100 text-red-600';
    const statusText = activity.status === 'completed' ? 'Confirmed' : 
                      activity.status === 'pending' ? 'Pending' : 
                      activity.status || 'Unknown';
    
    const timeAgo = formatTimeAgo(activity.date);
    
    row.innerHTML = `
        <td class="px-8 py-5">
            <p class="font-bold text-slate-900">${formatActionName(activity.action)}</p>
            <p class="text-xs text-slate-500">${activity.description || ''}</p>
        </td>
        <td class="px-4 py-5">
            <span class="${fundTypeBg} ${fundTypeText} text-[9px] font-black px-2 py-1 rounded uppercase">
                ${activity.fund_type === 'real' ? 'Real' : 'Profit Growth'}
            </span>
        </td>
        <td class="px-4 py-5 mono font-bold ${fundTypeText}">${amountDisplay}</td>
        <td class="px-8 py-5 text-right">
            <span class="${statusClass} text-[10px] font-bold px-3 py-1 rounded-full">${statusText}</span>
        </td>
    `;
    
    return row;
}

/**
 * Format action name for display
 */
function formatActionName(action) {
    const names = {
        'deposit': 'Deposit',
        'withdrawal': 'Withdrawal',
        'credit': 'Credit',
        'debit': 'Debit',
        'profit_growth_created': 'Profit Growth Created',
        'profit_growth_updated': 'Profit Growth Updated',
        'profit_growth_cancelled': 'Profit Growth Cancelled',
        'simulation_created': 'Profit Growth Created', // Legacy mapping
        'simulation_updated': 'Profit Growth Updated', // Legacy mapping
        'simulation_cancelled': 'Profit Growth Cancelled', // Legacy mapping
        'transfer_sent': 'Sent Sim Funds',
        'transfer_received': 'Funds Received',
        'admin_credit': 'Credit'
    };
    return names[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Handle user logout
 */
async function handleLogout() {
    try {
        await API.auth.logout();
        // Clear any stored tokens
        localStorage.removeItem('auth_token');
        // Redirect to login
        window.location.href = '/login.html';
    } catch (error) {
        console.error('Logout error:', error);
        // Even if logout fails, clear local storage and redirect
        localStorage.removeItem('auth_token');
        window.location.href = '/login.html';
    }
}

// Make logout function available globally
window.handleLogout = handleLogout;

/**
 * Format time ago
 */
function formatTimeAgo(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}

/**
 * Update community transfers section
 */
async function updateCommunityTransfers(data) {
    try {
        // Get recent sent and received transfers
        const [sentResponse, receivedResponse] = await Promise.all([
            API.user.getSentTransfers({ per_page: 3 }),
            API.user.getReceivedTransfers({ per_page: 3 })
        ]);
        
        const container = document.getElementById('communityTransfers');
        if (!container) return;
        
        // Clear existing content
        container.innerHTML = '';
        
        // Handle paginated responses - Laravel returns { data: [...], ... }
        const sent = sentResponse.data || sentResponse;
        const received = receivedResponse.data || receivedResponse;
        
        // Add received transfers
        if (Array.isArray(received) && received.length > 0) {
            received.forEach(transfer => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-start';
                const fromUser = transfer.from_user || transfer.fromUser || {};
                const timeAgo = formatTimeAgo(transfer.created_at);
                item.innerHTML = `
                    <div>
                        <p class="text-xs font-bold text-slate-900 uppercase tracking-tighter">Received</p>
                        <p class="text-sm text-slate-600">$${parseFloat(transfer.amount || 0).toFixed(2)} from ${fromUser.username || fromUser.name || 'User'}</p>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">${timeAgo}</span>
                `;
                container.appendChild(item);
            });
        }
        
        // Add sent transfers
        if (Array.isArray(sent) && sent.length > 0) {
            sent.forEach(transfer => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-start';
                const toUser = transfer.to_user || transfer.toUser || {};
                const timeAgo = formatTimeAgo(transfer.created_at);
                item.innerHTML = `
                    <div>
                        <p class="text-xs font-bold text-slate-900 uppercase tracking-tighter">Sent</p>
                        <p class="text-sm text-slate-600">$${parseFloat(transfer.amount || 0).toFixed(2)} to ${toUser.username || toUser.name || 'User'}</p>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">${timeAgo}</span>
                `;
                container.appendChild(item);
            });
        }
        
        // If no transfers, show empty state
        const hasTransfers = (Array.isArray(received) && received.length > 0) || (Array.isArray(sent) && sent.length > 0);
        if (!hasTransfers) {
            container.innerHTML = `
                <div class="text-center text-slate-400 py-4">
                    <p class="text-sm">No transfers yet</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Failed to load community transfers:', error);
    }
}

/**
 * Update statistics (if needed)
 */
function updateStatistics(stats) {
    // Statistics can be used for future enhancements
    if (stats) {
        console.log('Dashboard statistics:', stats);
    }
}

/**
 * Global unhandled promise rejection handler
 */
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled Promise Rejection:', event.reason);
    // You can add error reporting here
});

/**
 * Prompt the client to verify their email and enable 2FA until both are done.
 */
async function initAccountNudges() {
    if (typeof API === 'undefined' || !API.user?.getProfile) return;

    let user;
    try {
        const res = await API.user.getProfile();
        user = (res && res.user) || res;
    } catch (e) {
        return;
    }
    if (!user) return;

    const needsEmail = !user.email_verified_at;
    const needsTwoFactor = !user.two_factor_enabled;
    if (!needsEmail && !needsTwoFactor) return;

    const host = document.querySelector('main') || document.body;
    const wrap = document.createElement('div');
    wrap.className = 'space-y-3 mb-5';
    wrap.id = 'accountNudges';

    if (needsEmail) {
        const b = document.createElement('div');
        b.className = 'flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl border border-amber-200 bg-amber-50';
        b.innerHTML = `
            <div class="flex items-start gap-3 flex-1">
                <i class="fa-solid fa-envelope-circle-check text-amber-500 mt-0.5"></i>
                <div>
                    <p class="text-sm font-bold text-amber-900">Verify your email address</p>
                    <p class="text-xs text-amber-700">Confirm ${Utils.escapeHtml(user.email || 'your email')} so we can keep your account secure.</p>
                </div>
            </div>
            <div class="flex gap-2 shrink-0">
                <button data-nudge="resend" class="px-4 py-2 bg-white border border-amber-300 text-amber-800 rounded-lg text-xs font-bold hover:bg-amber-100 transition">Resend email</button>
                <a href="EMAIL_VERIFICATION_PAGE.html" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-xs font-bold hover:bg-amber-600 transition">Verify now</a>
            </div>`;
        b.querySelector('[data-nudge="resend"]')?.addEventListener('click', async function () {
            this.disabled = true;
            try {
                await API.auth.resendVerification();
                Utils?.showToast?.('Verification email sent', 'success');
            } catch (err) {
                Utils?.showToast?.(err.message || 'Could not send email', 'error');
            } finally {
                this.disabled = false;
            }
        });
        wrap.appendChild(b);
    }

    if (needsTwoFactor) {
        const b = document.createElement('div');
        b.className = 'flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl border border-slate-200 bg-white';
        b.innerHTML = `
            <div class="flex items-start gap-3 flex-1">
                <i class="fa-solid fa-shield-halved text-slate-500 mt-0.5"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">Add two-factor authentication</p>
                    <p class="text-xs text-slate-600">Protect withdrawals and sign-ins with an authenticator app.</p>
                </div>
            </div>
            <a href="SETTINGS_PAGE.html" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-black transition shrink-0">Set up 2FA</a>`;
        wrap.appendChild(b);
    }

    // Keep the payouts ticker above the nudges when present
    const ticker = document.getElementById('payoutsTicker');
    if (ticker && ticker.parentElement === host) {
        host.insertBefore(wrap, ticker.nextSibling);
    } else {
        host.insertBefore(wrap, host.firstChild);
    }
}
