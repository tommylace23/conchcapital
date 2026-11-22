/**
 * InvestEdu - API Helper Functions
 * Handles all API communication with the backend
 */

const API = {
    /**
     * Get CSRF token from cookies
     * @returns {string|null} CSRF token
     */
    getCsrfToken() {
        const name = 'XSRF-TOKEN';
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    },

    /**
     * Base API request function
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise} API response
     */
    async request(endpoint, options = {}) {
        const baseUrl = AppConfig?.api?.baseUrl || '';
        const url = `${baseUrl}${endpoint}`;

        const defaultOptions = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        // Add CSRF token for state-changing requests (POST, PUT, DELETE, PATCH)
        const method = (options.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                defaultOptions.headers['X-XSRF-TOKEN'] = csrfToken;
            }
        }

        // Add authentication token if available
        const token = localStorage.getItem('auth_token');
        if (token) {
            defaultOptions.headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);

            // Try to parse response as JSON
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                data = { message: text || `HTTP ${response.status} ${response.statusText}` };
            }

            if (!response.ok) {
                // Handle unauthenticated errors - redirect to login
                if (response.status === 401 || response.status === 419) {
                    // Clear any stored tokens
                    localStorage.removeItem('auth_token');
                    // Redirect to login page
                    if (window.location.pathname !== '/login.html' && !window.location.pathname.includes('login')) {
                        window.location.href = '/login.html';
                        return; // Don't throw error, we're redirecting
                    }
                }

                const error = new Error(`API Error: ${response.status} ${response.statusText}`);
                error.response = data;
                error.status = response.status;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            // If it's not already our custom error, wrap it
            if (!error.response) {
                const wrappedError = new Error(error.message || 'Network error');
                wrappedError.originalError = error;
                throw wrappedError;
            }
            throw error;
        }
    },

    /**
     * GET request
     * @param {string} endpoint - API endpoint
     * @param {Object} params - Query parameters
     * @returns {Promise} API response
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    /**
     * POST request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @returns {Promise} API response
     */
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * PUT request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data
     * @returns {Promise} API response
     */
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * DELETE request
     * @param {string} endpoint - API endpoint
     * @param {Object} data - Request body data (optional)
     * @returns {Promise} API response
     */
    async delete(endpoint, data = null) {
        const options = { method: 'DELETE' };
        if (data) {
            options.body = JSON.stringify(data);
        }
        return this.request(endpoint, options);
    },

    // Authentication endpoints
    auth: {
        async csrf() {
            return API.get('/sanctum/csrf-cookie');
        },

        async login(identifier, password, remember = false, twoFactorCode = null) {
            await API.auth.csrf();
            const data = { identifier, password, remember };
            if (twoFactorCode) {
                data.two_factor_code = twoFactorCode;
            }
            return API.post('/api/auth/login', data);
        },

        async verify2FALogin(twoFactorCode, remember = false) {
            await API.auth.csrf();
            return API.post('/api/auth/verify-2fa-login', { two_factor_code: twoFactorCode, remember });
        },

        async register(userData) {
            await API.auth.csrf();
            return API.post('/api/auth/register', userData);
        },

        async logout() {
            return API.post('/api/auth/logout');
        },

        async forgotPassword(email) {
            await API.auth.csrf();
            return API.post('/api/auth/forgot-password', { email });
        },

        async resetPassword(token, password, passwordConfirmation) {
            await API.auth.csrf();
            return API.post('/api/auth/reset-password', {
                token,
                password,
                password_confirmation: passwordConfirmation
            });
        },

        async verifyEmail() {
            await API.auth.csrf();
            return API.post('/api/auth/verify-email');
        },

        async resendVerification() {
            await API.auth.csrf();
            return API.post('/api/auth/resend-verification');
        }
    },

    // User endpoints
    user: {
        async getDashboard() {
            return API.get('/api/dashboard');
        },

        async getProfile() {
            return API.get('/api/user/profile');
        },

        async updateProfile(data, isFormData = false) {
            if (isFormData) {
                // For FormData, we need to send it without JSON headers
                const baseUrl = AppConfig?.api?.baseUrl || '';
                const url = `${baseUrl}/api/user/profile`;

                const options = {
                    method: 'POST', // Laravel handles PUT via _method
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: data
                };

                // Add CSRF token
                const csrfToken = API.getCsrfToken();
                if (csrfToken) {
                    options.headers['X-XSRF-TOKEN'] = csrfToken;
                }

                // Add authentication token
                const token = localStorage.getItem('auth_token');
                if (token) {
                    options.headers['Authorization'] = `Bearer ${token}`;
                }

                // Add _method for PUT
                data.append('_method', 'PUT');

                const response = await fetch(url, options);
                const result = await response.json();

                if (!response.ok) {
                    const error = new Error(`API Error: ${response.status} ${response.statusText}`);
                    error.response = result;
                    error.status = response.status;
                    throw error;
                }

                return result;
            } else {
                return API.put('/api/user/profile', data);
            }
        },

        async searchUsers(query = '', params = {}) {
            const searchParams = { ...params };
            if (query) {
                searchParams.q = query;
            }
            return API.get('/api/user/search', searchParams);
        },

        async getUserProfile(userId) {
            return API.get(`/api/user/profile/${userId}`);
        },

        async getUserPortfolio(userId) {
            return API.get(`/api/user/profile/${userId}/portfolio`);
        },

        // Profile View Requests
        async createProfileViewRequest(profileOwnerId) {
            return API.post('/api/profile-view-requests/create', { profile_owner_id: profileOwnerId });
        },

        async getPendingProfileViewRequests() {
            return API.get('/api/profile-view-requests/pending');
        },

        async approveProfileViewRequest(requestId) {
            return API.post(`/api/profile-view-requests/${requestId}/approve`);
        },

        async declineProfileViewRequest(requestId) {
            return API.post(`/api/profile-view-requests/${requestId}/decline`);
        },

        async checkProfileViewAccess(profileOwnerId) {
            return API.get(`/api/profile-view-requests/check-access/${profileOwnerId}`);
        },

        async getTransactions(params = {}) {
            return API.get('/api/user/transactions', params);
        },

        async deposit(data) {
            return API.post('/api/user/transactions/deposit', data);
        },

        async withdrawal(data) {
            return API.post('/api/user/transactions/withdrawal', data);
        },

        // Settings
        async getSettings() {
            return API.get('/api/user/settings');
        },

        async updateAccountSettings(data) {
            return API.put('/api/user/settings/account', data);
        },

        async updateNotificationPreferences(data) {
            return API.put('/api/user/settings/notifications', data);
        },

        async changePassword(currentPassword, newPassword, newPasswordConfirmation) {
            return API.post('/api/user/settings/change-password', {
                current_password: currentPassword,
                password: newPassword,
                password_confirmation: newPasswordConfirmation
            });
        },

        async changeEmail(newEmail, password) {
            return API.post('/api/user/settings/change-email', {
                email: newEmail,
                password: password
            });
        },

        async getActiveSessions() {
            return API.get('/api/user/settings/sessions');
        },

        async revokeSession(sessionId) {
            return API.delete(`/api/user/settings/sessions/${sessionId}`);
        },

        async revokeAllOtherSessions() {
            return API.delete('/api/user/settings/sessions');
        },

        async initialize2FA() {
            return API.post('/api/user/settings/2fa/initialize');
        },

        async enable2FA(secret, verificationCode) {
            return API.post('/api/user/settings/2fa/enable', {
                secret: secret,
                verification_code: verificationCode
            });
        },

        async disable2FA(password) {
            return API.post('/api/user/settings/2fa/disable', {
                password: password
            });
        },

        async deleteAccount(password, confirmation) {
            return API.delete('/api/user/settings/account', {
                password: password,
                confirmation: confirmation
            });
        },

        // Profit Growths (new)
        async getProfitGrowths(params = {}) {
            const response = await API.get('/api/user/profit-growths', params);
            // Backend returns paginated response with 'data' key
            // Return as-is so callers can access response.data
            return response;
        },

        async getProfitGrowth(id) {
            const response = await API.get(`/api/user/profit-growths/${id}`);
            // Backend returns 'profit_growth', normalize to 'simulation' for frontend compatibility
            if (response.profit_growth) {
                return { simulation: response.profit_growth };
            }
            return response;
        },

        async createProfitGrowth(data) {
            const response = await API.post('/api/user/profit-growths', data);
            // Backend returns 'profit_growth', normalize to 'simulation' for frontend compatibility
            if (response.profit_growth) {
                return { simulation: response.profit_growth };
            }
            return response;
        },

        async updateProfitGrowth(id, data) {
            const response = await API.put(`/api/user/profit-growths/${id}`, data);
            // Backend returns 'profit_growth', normalize to 'simulation' for frontend compatibility
            if (response.profit_growth) {
                return { simulation: response.profit_growth };
            }
            return response;
        },

        async deleteProfitGrowth(id) {
            return API.delete(`/api/user/profit-growths/${id}`);
        },

        async transferFundsToProfitGrowth(id, amount, options = {}) {
            // If no ID provided, use the new endpoint that auto-creates profit growth
            // Users cannot set percentage/frequency - defaults to 20% weekly (admin can change)
            if (!id || id === '') {
                return API.post('/api/user/profit-growths/transfer-funds', {
                    amount: amount,
                    name: options.name || null,
                    // growth_percentage and growth_frequency removed - users cannot set these
                });
            }
            // Transfer to existing profit growth
            return API.post(`/api/user/profit-growths/${id}/transfer-funds`, {
                amount: amount
            });
        },

        async withdrawFundsFromProfitGrowth(id, amount) {
            return API.post(`/api/user/profit-growths/${id}/withdraw-funds`, {
                amount: amount
            });
        },

        // Legacy simulation methods (for backward compatibility - redirect to profit growths)
        async getSimulations(params = {}) {
            return API.user.getProfitGrowths(params);
        },

        async getSimulation(id) {
            return API.user.getProfitGrowth(id);
        },

        async createSimulation(data) {
            return API.user.createProfitGrowth(data);
        },

        async updateSimulation(id, data) {
            return API.user.updateProfitGrowth(id, data);
        },

        async deleteSimulation(id) {
            return API.user.deleteProfitGrowth(id);
        },

        async getSimulatedTransfers(params = {}) {
            return API.get('/api/user/simulated-transfers', params);
        },

        async getSentTransfers(params = {}) {
            return API.get('/api/user/simulated-transfers/sent', params);
        },

        async getReceivedTransfers(params = {}) {
            return API.get('/api/user/simulated-transfers/received', params);
        },

        async sendSimulatedFunds(data) {
            // If data has simulation_id, convert it to profit_growth_id for the new API
            const payload = { ...data };
            if (payload.simulation_id && !payload.profit_growth_id) {
                payload.profit_growth_id = payload.simulation_id;
                delete payload.simulation_id;
            }
            return API.post('/api/user/simulated-transfers', payload);
        }
    },

    // Admin endpoints
    admin: {
        async getUsers(params = {}) {
            return API.get('/api/admin/users', params);
        },

        async getUser(userId) {
            return API.get(`/api/admin/users/${userId}`);
        },

        async creditFunds(userId, amount, reason, fundType = 'real', description = null, direction = 'credit') {
            return API.post('/api/admin/credits', {
                user_id: userId,
                amount,
                fund_type: fundType,
                direction,
                currency: 'USD',
                reason: reason || null,
                description: description || reason || null
            });
        },

        async freezeAccount(userId, data) {
            return API.post(`/api/admin/users/${userId}/freeze`, data);
        },

        async unfreezeAccount(userId, data) {
            return API.post(`/api/admin/users/${userId}/unfreeze`, data);
        },

        async setReferralBonus(userId, amount) {
            return API.post(`/api/admin/users/${userId}/referral-bonus`, {
                referral_bonus_amount: amount
            });
        },

        async getUserProfitGrowths(userId) {
            return API.get(`/api/admin/users/${userId}/profit-growths`);
        },

        async getProfitGrowth(profitGrowthId) {
            return API.get(`/api/admin/profit-growths/${profitGrowthId}`);
        },

        async bulkUsers(action, userIds, reason = null) {
            return API.post('/api/admin/users/bulk', { action, user_ids: userIds, reason });
        },

        async impersonate(userId) {
            return API.post(`/api/admin/users/${userId}/impersonate`);
        },

        async stopImpersonation() {
            return API.post('/api/impersonation/stop');
        },

        async impersonationStatus() {
            return API.get('/api/impersonation/status');
        },

        async getAdmins() {
            return API.get('/api/admin/admins');
        },

        async createAdmin(data) {
            return API.post('/api/admin/admins', data);
        },

        async getActionsLog(params = {}) {
            return API.get('/api/admin/actions-log', params);
        },

        async getSimulatedTransfers(params = {}) {
            return API.get('/api/admin/simulated-transfers', params);
        },

        async getFrozenAccounts(params = {}) {
            return API.get('/api/admin/frozen-accounts', params);
        },

        async getPlatformSettings() {
            return API.get('/api/admin/platform-settings');
        },

        async updatePlatformSettings(data) {
            return API.put('/api/admin/platform-settings', data);
        },

        async getBankSettings() {
            return API.get('/api/admin/bank-settings');
        },

        async updateBankSettings(data) {
            return API.put('/api/admin/bank-settings', data);
        },

        async resetBankSettings() {
            return API.post('/api/admin/bank-settings/reset');
        },

        async deleteUser(userId) {
            return API.delete(`/api/admin/users/${userId}`);
        },

        async getTransactions(params = {}) {
            return API.get('/api/admin/transactions', params);
        },

        async getReports(params = {}) {
            return API.get('/api/admin/reports', params);
        },

        async createProfitGrowth(data) {
            return API.post('/api/admin/profit-growths', data);
        },

        async triggerGrowthCalculation(profitGrowthId = null, force = false) {
            return API.post('/api/admin/profit-growths/calculate-growth', {
                profit_growth_id: profitGrowthId,
                force: force
            });
        },

        async updateProfitGrowth(id, data) {
            return API.put(`/api/admin/profit-growths/${id}`, data);
        },

        async deleteProfitGrowth(id) {
            return API.delete(`/api/admin/profit-growths/${id}`);
        },

        async getPendingDeposits() {
            return API.get('/api/admin/deposits/pending');
        },

        async confirmDeposit(depositId) {
            return API.post(`/api/admin/deposits/${depositId}/confirm`);
        },

        async checkDepositStatus(depositId) {
            return API.post(`/api/admin/deposits/${depositId}/check`);
        },

        async getPendingWithdrawals() {
            return API.get('/api/admin/withdrawals/pending');
        },

        async confirmWithdrawal(withdrawalId) {
            return API.post(`/api/admin/withdrawals/${withdrawalId}/confirm`);
        },

        async cancelWithdrawal(withdrawalId, reason = null) {
            return API.post(`/api/admin/withdrawals/${withdrawalId}/cancel`, {
                reason: reason || null
            });
        },

        async createWithdrawal(userId, data) {
            return API.post('/api/admin/withdrawals', {
                user_id: userId,
                ...data
            });
        },

        async cleanupPendingDepositActivityLogs() {
            return API.post('/api/admin/activity-logs/cleanup-pending-deposits');
        },

        async checkUserProfitGrowth(userId) {
            return API.get(`/api/admin/users/${userId}/profit-growth-check`);
        }
    },

    // Newsletter endpoints
    newsletter: {
        async subscribe(email) {
            await API.auth.csrf();
            return API.post('/api/newsletter/subscribe', { email });
        },

        async unsubscribe(email) {
            await API.auth.csrf();
            return API.post('/api/newsletter/unsubscribe', { email });
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = API;
}
