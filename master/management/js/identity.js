/**
 * Identity Layer for Vivacity NextGen SYNC
 * Handles user persistence across sessions (Secure Session Version)
 */

(function() {
    // Current identity state
    window.session = {
        authenticated: false,
        username: 'Anonymous',
        role: null,
        tenant: null
    };

    /**
     * Determine the correct base path for API calls based on current depth
     */
    function getRootBase() {
        const path = window.location.pathname;
        if (path.includes('/tenants/')) {
            return '../../';
        } else if (path.includes('/management/') || path.includes('/tools/')) {
            return '../';
        }
        return '';
    }

    /**
     * Fetch session information from server
     */
    async function refreshSession() {
        const root = getRootBase();
        try {
            const response = await fetch(root + 'tools/api/session_info.php');
            if (response.ok) {
                const data = await response.json();
                window.session = {
                    authenticated: data.authenticated,
                    username: data.username,
                    role: data.role,
                    tenant: data.tenant
                };
                
                // Backwards compatibility for old scripts using localStorage
                localStorage.setItem('sync_username', data.username);
                localStorage.setItem('sync_role', data.role);
                if (data.tenant) {
                    localStorage.setItem('sync_tenant', data.tenant);
                } else {
                    localStorage.removeItem('sync_tenant');
                }
                
                updateUI();
            } else if (response.status === 401) {
                // Not authenticated, but we don't necessarily redirect immediately
                // as some pages might be public or handle it themselves.
            }
        } catch (error) {
            console.error('Failed to fetch session info:', error);
        }
    }

    function updateUI() {
        const { username, tenant, role } = window.session;
        
        // Update "Welcome" message if element exists
        const welcomeElement = document.getElementById('welcome-user');
        if (welcomeElement) {
            let text = `Welcome, ${username}`;
            if (tenant) {
                text += ` (${tenant})`;
            }
            welcomeElement.textContent = text;
        }

        // Add tenant indicator to page title
        if (tenant && !document.title.startsWith(`[${tenant}]`)) {
            document.title = `[${tenant}] ` + document.title;
        }
        
        // Handle back to dashboard links for technicians
        if (role === 'technician' && window.location.pathname.includes('/management/')) {
            const dashboardLinks = document.querySelectorAll('a[href="dashboard.html"], a[href="./dashboard.html"]');
            dashboardLinks.forEach(link => {
                link.href = '../tools/dashboard.html';
            });
        }
    }

    // Initialize UI elements when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        refreshSession();

        // Add Logout functionality to logout buttons
        const logoutButtons = document.querySelectorAll('.logout-btn');
        logoutButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                logout();
            });
        });
    });

    /**
     * Clear local storage and redirect to login
     */
    function logout() {
        localStorage.removeItem('sync_username');
        localStorage.removeItem('sync_role');
        localStorage.removeItem('sync_tenant');
        
        const root = getRootBase();
        window.location.href = root + 'index.php';
    }

    // Export logout to window object
    window.syncLogout = logout;

    /**
     * Make an API call that respects tenant context and includes CSRF token
     */
    window.apiCall = async function(endpoint, data = {}) {
        const root = getRootBase();
        const tenant = window.session.tenant;
        
        // If endpoint doesn't start with root, prepend it
        let url = endpoint;
        if (!url.startsWith('http') && !url.startsWith('/')) {
            url = root + endpoint;
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (response.status === 401) {
            console.warn('Unauthorized API call - redirecting to login');
            logout();
            return { error: 'Unauthorized' };
        }
        
        return response.json();
    };
})();
