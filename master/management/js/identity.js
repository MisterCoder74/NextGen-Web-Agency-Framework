/**
 * Identity Layer for Vivacity NextGen SYNC - Tenant Edition
 * Handles user persistence across sessions within a tenant context
 */

(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlUser = urlParams.get('u');
    const urlRole = urlParams.get('r');
    const urlTenant = urlParams.get('tenant');

    if (urlUser) {
        localStorage.setItem('sync_username', urlUser);
    }
    if (urlRole) {
        localStorage.setItem('sync_role', urlRole);
    }
    if (urlTenant) {
        localStorage.setItem('sync_tenant', urlTenant);
    }

    let updateUrl = false;
    const storedUser = localStorage.getItem('sync_username');
    const storedRole = localStorage.getItem('sync_role');
    const storedTenant = localStorage.getItem('sync_tenant');

    if (!urlUser && storedUser) {
        urlParams.set('u', storedUser);
        updateUrl = true;
    }
    if (!urlRole && storedRole) {
        urlParams.set('r', storedRole);
        updateUrl = true;
    }
    if (!urlTenant && storedTenant) {
        urlParams.set('tenant', storedTenant);
        updateUrl = true;
    }

    if (updateUrl) {
        const paramsString = urlParams.toString();
        const newUrl = window.location.pathname + (paramsString ? '?' + paramsString : '');
        window.history.replaceState({}, document.title, newUrl);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const username = localStorage.getItem('sync_username') || 'Anonymous';
        const tenant = localStorage.getItem('sync_tenant');

        const welcomeElement = document.getElementById('welcome-user');
        if (welcomeElement) {
            let welcomeText = `Welcome, ${username}`;
            if (tenant) {
                welcomeText += ` (${tenant})`;
            }
            welcomeElement.textContent = welcomeText;
        }

        const logoutButtons = document.querySelectorAll('.logout-btn');
        logoutButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                logout();
            });
        });

        const userRole = localStorage.getItem('sync_role');
        if (userRole === 'technician' && window.location.pathname.includes('/management/')) {
            const dashboardLinks = document.querySelectorAll('a[href="dashboard.html"], a[href="./dashboard.html"]');
            dashboardLinks.forEach(link => {
                if (!link.href.includes('/tools/')) {
                    link.href = '../tools/dashboard.html';
                }
            });

            const allLinks = document.querySelectorAll('a');
            allLinks.forEach(link => {
                const text = link.textContent.trim().toLowerCase();
                if (text.includes('back to dashboard')) {
                    const currentHref = link.getAttribute('href');
                    if (currentHref === 'dashboard.html' || currentHref === './dashboard.html' || !currentHref) {
                        link.href = '../tools/dashboard.html';
                    }
                }
            });
        }

        if (tenant) {
            document.title = `[${tenant}] ` + document.title;
        }
    });

    function logout() {
        const tenant = localStorage.getItem('sync_tenant');
        localStorage.removeItem('sync_username');
        localStorage.removeItem('sync_role');
        localStorage.removeItem('sync_tenant');

        if (tenant) {
            window.location.href = '../../index.php';
        } else if (window.location.pathname.includes('/management/') || window.location.pathname.includes('/tools/')) {
            window.location.href = '../index.php';
        } else {
            window.location.href = 'index.php';
        }
    }

    window.syncLogout = logout;

    window.getTenantBase = function() {
        const tenant = localStorage.getItem('sync_tenant');
        if (!tenant) return '';
        return '../../';
    };

    window.apiCall = async function(endpoint, data = {}) {
        const tenant = localStorage.getItem('sync_tenant');
        const base = window.getTenantBase() || '';
        const url = base + endpoint + (tenant ? (endpoint.includes('?') ? '&' : '?') + 'tenant=' + encodeURIComponent(tenant) : '');

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    };
})();
