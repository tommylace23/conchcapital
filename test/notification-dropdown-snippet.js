// Notification Dropdown Functions - Reusable snippet
window.toggleNotificationDropdown = function() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        const isHidden = dropdown.classList.contains('hidden');
        dropdown.classList.toggle('hidden');
        if (isHidden) {
            loadNotificationDropdown();
        }
    }
};

async function loadNotificationDropdown() {
    const dropdownList = document.getElementById('notificationDropdownList');
    if (!dropdownList) return;
    try {
        dropdownList.innerHTML = '<div class="p-4 text-center text-slate-500"><i class="fa-solid fa-spinner fa-spin text-xl mb-2"></i><p class="text-sm">Loading notifications...</p></div>';
        const dashboardData = await API.user.getDashboard();
        const transactions = dashboardData.recent_transactions || [];
        const activityLogs = dashboardData.recent_activity || [];
        const allNotifications = [];
        transactions.slice(0, 10).forEach(tx => {
            allNotifications.push({ type: 'transaction', id: tx.id, action: tx.type, amount: tx.amount, currency: tx.currency || 'USD', description: tx.description || '', status: tx.status, created_at: tx.created_at });
        });
        activityLogs.slice(0, 10).forEach(activity => {
            allNotifications.push({ type: 'activity', id: activity.id, action: activity.action, description: activity.description || '', created_at: activity.created_at });
        });
        allNotifications.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        const recentNotifications = allNotifications.slice(0, 5);
        const badge = document.getElementById('notificationBadge');
        if (badge) badge.classList.toggle('hidden', recentNotifications.length === 0);
        if (recentNotifications.length === 0) {
            dropdownList.innerHTML = '<div class="p-4 text-center text-slate-500"><p class="text-sm">No notifications</p></div>';
            return;
        }
        dropdownList.innerHTML = recentNotifications.map(notif => {
            const date = formatNotificationDate(notif.created_at);
            const icon = getNotificationIcon(notif.action || notif.type);
            const title = getNotificationTitle(notif);
            const description = notif.description || '';
            return `<div class="p-4 hover:bg-gray-50 transition cursor-pointer" onclick="handleNotificationClick('${notif.type}', ${notif.id})"><div class="flex items-start gap-3"><div class="w-10 h-10 rounded-full ${icon.bg} flex items-center justify-center shrink-0"><i class="fa-solid ${icon.icon} ${icon.color} text-sm"></i></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-slate-800 mb-1">${title}</p>${description ? `<p class="text-xs text-slate-500 line-clamp-2 mb-1">${description}</p>` : ''}<p class="text-xs text-slate-400">${date}</p></div></div></div>`;
        }).join('');
    } catch (error) {
        console.error('Failed to load notifications:', error);
        dropdownList.innerHTML = '<div class="p-4 text-center text-red-500"><p class="text-sm">Failed to load notifications</p></div>';
    }
}

function formatNotificationDate(dateString) {
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
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function getNotificationIcon(action) {
    const icons = { 'deposit': { icon: 'fa-arrow-down', color: 'text-green-600', bg: 'bg-green-100' }, 'withdrawal': { icon: 'fa-arrow-up', color: 'text-red-600', bg: 'bg-red-100' }, 'credit': { icon: 'fa-gift', color: 'text-blue-600', bg: 'bg-blue-100' }, 'debit': { icon: 'fa-minus', color: 'text-orange-600', bg: 'bg-orange-100' }, 'transfer_sent': { icon: 'fa-paper-plane', color: 'text-blue-600', bg: 'bg-blue-100' }, 'transfer_received': { icon: 'fa-inbox', color: 'text-green-600', bg: 'bg-green-100' }, 'profit_growth_created': { icon: 'fa-chart-line', color: 'text-purple-600', bg: 'bg-purple-100' }, 'profit_growth_updated': { icon: 'fa-sync', color: 'text-blue-600', bg: 'bg-blue-100' } };
    return icons[action] || { icon: 'fa-bell', color: 'text-slate-600', bg: 'bg-slate-100' };
}

function getNotificationTitle(notif) {
    const action = notif.action || notif.type;
    const actionNames = { 'deposit': 'Deposit', 'withdrawal': 'Withdrawal', 'credit': 'Credit', 'debit': 'Debit', 'transfer_sent': 'Funds Sent', 'transfer_received': 'Funds Received', 'profit_growth_created': 'Profit Growth Created', 'profit_growth_updated': 'Profit Growth Updated' };
    const actionName = actionNames[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    if (notif.amount) {
        const amount = parseFloat(notif.amount);
        const sign = action === 'deposit' || action === 'credit' || action === 'transfer_received' ? '+' : '-';
        return `${actionName}: ${sign}$${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
    return actionName;
}

window.handleNotificationClick = function(type, id) {
    window.location.href = type === 'transaction' ? '#transactions' : '#notifications';
};
