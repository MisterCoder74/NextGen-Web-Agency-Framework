/**
 * Identity Layer for Vivacity NextGen SYNC
 * Handles user persistence across sessions
 */

(function() {
    // Parse username and role from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const urlUser = urlParams.get('u');
    const urlRole = urlParams.get('r');

    if (urlUser) {
        localStorage.setItem('sync_username', urlUser);
    }
    if (urlRole) {
        localStorage.setItem('sync_role', urlRole);
    }

    // Ensure persistence in the URL
    let updateUrl = false;
    const storedUser = localStorage.getItem('sync_username');
    const storedRole = localStorage.getItem('sync_role');

    if (!urlUser && storedUser) {
        urlParams.set('u', storedUser);
        updateUrl = true;
    }
    if (!urlRole && storedRole) {
        urlParams.set('r', storedRole);
        updateUrl = true;
    }

    if (updateUrl) {
        const paramsString = urlParams.toString();
        const newUrl = window.location.pathname + (paramsString ? '?' + paramsString : '');
        window.history.replaceState({}, document.title, newUrl);
    }

    // Initialize UI elements when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        const username = localStorage.getItem('sync_username') || 'Anonymous';
        
        // Update "Welcome" message if element exists
        const welcomeElement = document.getElementById('welcome-user');
        if (welcomeElement) {
            welcomeElement.textContent = `Welcome, ${username}`;
        }

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
        // Determine redirect path based on current location
        if (window.location.pathname.includes('/management/') || window.location.pathname.includes('/tools/')) {
            window.location.href = '../index.php';
        } else {
            window.location.href = 'index.php';
        }
    }

    // Export logout to window object
    window.syncLogout = logout;
})();
