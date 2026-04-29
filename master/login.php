<?php
/**
 * Tenant Login Handler
 * Supports both global and tenant-scoped authentication.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $tenantSlug = filter_input(INPUT_POST, 'tenant', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($username && $password) {
        // Determine which users file to check
        $usersFile = 'users.json';
        $tenantContext = null;

        // If tenant is specified, check tenant's users.json first
        if ($tenantSlug) {
            $tenantPath = __DIR__ . '/tenants/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantSlug);
            $tenantSlashParts = glob(__DIR__ . '/tenants/*' . preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantSlug));
            foreach ($tenantSlashParts as $tp) {
                if (is_dir($tp) && strpos(basename($tp), '___' . preg_replace('/[^a-zA-Z0-9_-]/', '', $tenantSlug)) !== false) {
                    $tenantPath = $tp;
                    break;
                }
            }

            $tenantUsersFile = $tenantPath . '/users.json';

            if (is_dir($tenantPath) && file_exists($tenantUsersFile)) {
                // Load tenant registry to check status
                $registryFile = __DIR__ . '/tenants.json';
                $tenants = [];
                if (file_exists($registryFile)) {
                    $tenants = json_decode(file_get_contents($registryFile), true) ?: [];
                }

                $currentTenant = null;
                foreach ($tenants as $t) {
                    if ($t['slug'] === $tenantSlug) {
                        $currentTenant = $t;
                        break;
                    }
                }

                if ($currentTenant && $currentTenant['status'] === 'suspended') {
                    header('Location: index.php?error=5&tenant=' . urlencode($tenantSlug));
                    exit;
                }

                $tenantUsers = json_decode(file_get_contents($tenantUsersFile), true) ?: [];
                $authenticated = false;
                $role = 'technician';

                foreach ($tenantUsers as $user) {
                    if ($user['username'] === $username && $user['password'] === $password) {
                        $authenticated = true;
                        $role = $user['role'] ?? 'technician';
                        $tenantContext = $currentTenant;
                        break;
                    }
                }

                if ($authenticated) {
                    // Check if tenant needs first-login activation
                    $activatedFile = $tenantPath . '/.activated';
                    $needsActivation = !file_exists($activatedFile);
                    $firstLogin = isset($user['first_login']) ? $user['first_login'] : $needsActivation;

                    // Mark first login done
                    if ($needsActivation) {
                        foreach ($tenantUsers as &$u) {
                            if ($u['username'] === $username) {
                                $u['first_login_done'] = true;
                                break;
                            }
                        }
                        file_put_contents($tenantUsersFile, json_encode($tenantUsers, JSON_PRETTY_PRINT));
                    }

                    $redirect = $tenantPath . '/management/dashboard.html?u=' . urlencode($username) . '&r=' . urlencode($role) . '&tenant=' . urlencode($tenantSlug);
                    if ($needsActivation) {
                        $redirect .= '&first_login=1';
                    }
                    header('Location: ' . $redirect);
                    exit;
                }
            }
        }

        // Fall back to global users.json
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);

            $authenticated = false;
            $role = 'technician';

            foreach ($users as $user) {
                if ($user['username'] === $username && $user['password'] === $password) {
                    $authenticated = true;
                    $role = $user['role'] ?? 'technician';
                    break;
                }
            }

            if ($authenticated) {
                if ($role === 'management') {
                    header('Location: ./management/dashboard.html?u=' . urlencode($username) . '&r=' . urlencode($role));
                } else {
                    header('Location: ./tools/dashboard.html?u=' . urlencode($username) . '&r=' . urlencode($role));
                }
                exit;
            }
        }

        // All authentication failed
        if ($tenantSlug) {
            header('Location: index.php?error=1&tenant=' . urlencode($tenantSlug));
        } else {
            header('Location: index.php?error=1');
        }
        exit;
    } else {
        header('Location: index.php?error=3' . ($tenantSlug ? '&tenant=' . urlencode($tenantSlug) : ''));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>
